<?php

namespace Ezzaze\SsimParser;

use Carbon\Carbon;
use Ezzaze\SsimParser\Contracts\SsimVersionContract;
use Ezzaze\SsimParser\Exceptions\{EmptyDataSourceException, InvalidRegexClassException};
use PharIo\Version\InvalidVersionException;

class SsimParser
{
    protected string $rawData;
    protected int $version;
    protected string $ssimVersion;
    protected array $dataLines = [];
    protected array $recordTypesSupported = [];

    public function __construct()
    {
        $this->recordTypesSupported = $this->getSupportedVersions();
        $this->setVersion(\Ezzaze\SsimParser\Versions\Version3::class);
    }

    /**
     * Get list of supported SSIM versions
     *
     * @return array
     */
    protected function getSupportedVersions(): array
    {
        $versions = [];
        foreach (get_declared_classes() as $className) {
            if (in_array(SsimVersionContract::class, class_implements($className))) {
                $versions[] = (new \ReflectionClass($className))->newInstance()->getName();
            }
        }

        return $versions;
    }

    /**
     * Load SSIM data from provided source
     *
     * @param  string $source filepath or raw data
     * @return self
     * @throws EmptyDataSourceException
     */
    public function load(string $source): self
    {
        if (empty($source)) {
            throw new EmptyDataSourceException("Data source cannot be empty.");
        }
        $this->rawData = is_file($source) ? file_get_contents($source) : $source;
        $this->dataLines = preg_split('/\r\n|\r|\n/', $this->rawData);

        return $this;
    }

    /**
     * Set the SSIM version to extract manually
     *
     * @param  string $version_class
     * @return self
     * @throws InvalidVersionException
     */
    public function setVersion(string $version_class): self
    {
        if (! class_exists($version_class)) {
            throw new InvalidVersionException("Class {$version_class} does not exist.");
        }

        $class = new \ReflectionClass($version_class);
        if (! $class->implementsInterface(SsimVersionContract::class)) {
            throw new InvalidVersionException("Class {$version_class} must implement SsimVersionContract interface.");
        }
        $this->version = ($class->newInstance())::getName();

        return $this;
    }

    /**
     * Parse the SSIM data and retrieve the results
     *
     * @return array
     */
    public function parse(): array
    {
        $output = [];
        foreach ($this->dataLines as $line) {
            if (str_split(trim($line))[0] != $this->version) {
                continue;
            }
            $output = array_merge_recursive($output, $this->extractData($line));
        }

        return $this->sort($output, 'departure_utc_datetime');
    }

    /**
     * Extract all the relevant data from SSIM line
     *
     * @param  string $data
     * @return array
     * @throws
     */
    private function extractData(string $data): array
    {
        if (! class_exists('Ezzaze\SsimParser\Regexes\Version' . $this->version)) {
            throw new InvalidRegexClassException("Regex class 'Ezzaze\SsimParser\Regexes\Version{$this->version}' does not exist");
        }

        $object = (object)[];
        $class = new \ReflectionClass('Ezzaze\SsimParser\Regexes\Version' . $this->version);
        foreach ($class->getConstants() as $name => $regex) {
            preg_match($regex, $data, $matches);
            if (! in_array($regex, $class->newInstance()->getHiddenAttributes())) {
                $object->{strtolower($name)} = trim($matches[strtolower($name)]) ?? null;
            }
            $data = preg_replace($regex, '', $data, 1);
        }

        return $this->formatResult($object);
    }

    /**
     * Format the resulting data
     *
     * @param  mixed $data
     * @return array
     */
    private function formatResult(mixed $data): array
    {
        $flights = [];
        $startDate = date('Y-m-d', strtotime($data->operation_start_date));
        $endDate = date('Y-m-d', strtotime($data->operation_end_date));
        $operation_dates = $this->getDatesInInterval($startDate, $endDate, str_split($data->operation_days_of_week));
        foreach ($operation_dates as $date) {
            $local_departure = Carbon::parse($date . ' ' . $data->aircraft_departure_time . $data->utc_local_departure_time_variant);
            $local_arrival = Carbon::parse($date . ' ' . $data->aircraft_arrival_time . $data->utc_local_arrival_time_variant)->addDays(intVal($data->date_variation));
            $utc_departure = (clone $local_departure)->setTimezone('UTC');
            $utc_arrival = (clone $local_arrival)->setTimezone('UTC');

            $flights[] = [
                "airline_designator" => $data->airline_designator,
                "service_type" => $data->service_type,
                "flight_number" => $data->flight_number,
                "departure_datetime" => $local_departure->format('Y-m-d H:i:s'),
                "arrival_datetime" => $local_arrival->format('Y-m-d H:i:s'),
                "departure_utc_datetime" => $utc_departure->format('Y-m-d H:i:s'),
                "arrival_utc_datetime" => $utc_arrival->format('Y-m-d H:i:s'),
                "departure_iata" => $data->departure_station,
                "arrival_iata" => $data->arrival_station,
                "aicraft_type" => $data->aircraft_type,
                "aicraft_configuration" => $data->aircraft_configuration_version,
            ];
        }

        return $flights;
    }

    /**
     * Get all dates in the given interval matching the days of week
     *
     * @param  string $startDate
     * @param  string $endDate
     * @param  array $daysOfWeek
     * @return array
     */
    private function getDatesInInterval(string $startDate, string $endDate, array $daysOfWeek = []): array
    {
        $begin = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $end->setTime(0, 0, 1);

        $result = [];

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($begin, $interval, $end);
        foreach ($period as $dt) {
            $numOfDay = date("N", $dt->format('U'));
            if (in_array($numOfDay, $daysOfWeek)) {
                $result[] = $dt->format('Y-m-d');
            }
        }

        return $result;
    }

    /**
     * Sort given array
     *
     * @param  array $data
     * @param  string $key
     * @return array
     */
    private function sort(array $data, string $key): array
    {
        usort($data, function ($a, $b) use ($key) {
            if ($a[$key] < $b[$key]) {
                return -1;
            } elseif ($a[$key] > $b[$key]) {
                return 1;
            }

            return 0;
        });

        return $data;
    }
}
