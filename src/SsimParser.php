<?php

declare(strict_types=1);

namespace Ezzaze\SsimParser;

use Carbon\Carbon;
use Ezzaze\SsimParser\Contracts\SsimRegexContract;
use Ezzaze\SsimParser\Contracts\SsimVersionContract;
use Ezzaze\SsimParser\Exceptions\EmptyDataSourceException;
use Ezzaze\SsimParser\Exceptions\FileReadException;
use Ezzaze\SsimParser\Exceptions\InvalidContractException;
use Ezzaze\SsimParser\Exceptions\InvalidInputException;
use Ezzaze\SsimParser\Exceptions\InvalidRegexClassException;
use Ezzaze\SsimParser\Exceptions\InvalidVersionClassException;
use Ezzaze\SsimParser\Regexes\Version3 as Version3Regex;
use Ezzaze\SsimParser\Versions\Version3;

class SsimParser
{
    protected string $rawData;
    protected int $versionNumber;
    protected SsimRegexContract $regexInstance;

    /** @var array<string, string> */
    protected array $regexConstants = [];

    /** @var list<string> */
    protected array $hiddenAttributes = [];

    /** @var list<string> */
    protected array $dataLines = [];

    /**
     * Constructor for SsimParser.
     *
     * Initializes the parser with the provided or default implementations of SsimVersionContract and SsimRegexContract.
     *
     * @param SsimVersionContract|null $version The version implementation to use. Defaults to Version3.
     * @param SsimRegexContract|null $regex The regex implementation to use. Defaults to Version3Regex.
     */
    public function __construct(?SsimVersionContract $version = null, ?SsimRegexContract $regex = null)
    {
        $versionImpl = $version ?? new Version3();
        $regexImpl = $regex ?? new Version3Regex();

        $this->versionNumber = $versionImpl::getName();
        $this->setRegexInstance($regexImpl);
    }

    /**
     * Load SSIM data from a provided source.
     *
     * The source can be either a file path or raw data string.
     *
     * @param string $source The file path or raw data string.
     * @return self Returns the current instance for method chaining.
     * @throws EmptyDataSourceException If the provided source is empty.
     * @throws FileReadException If the file exists but cannot be read.
     */
    public function load(string $source): self
    {
        if (empty($source)) {
            throw new EmptyDataSourceException("Data source cannot be empty.");
        }

        if (is_file($source)) {
            $contents = @file_get_contents($source);
            if ($contents === false) {
                throw new FileReadException("Unable to read file: {$source}");
            }
            $this->rawData = $contents;
        } else {
            $this->rawData = $source;
        }

        $this->dataLines = preg_split('/\r\n|\r|\n/', $this->rawData) ?: [];

        return $this;
    }

    /**
     * Set the SSIM version to use for parsing.
     *
     * @param string $version_class The fully qualified class name of the version implementation.
     * @return self Returns the current instance for method chaining.
     * @throws InvalidVersionClassException If the provided class does not exist.
     * @throws InvalidContractException If the provided class does not implement SsimVersionContract.
     */
    public function setVersion(string $version_class): self
    {
        if (! class_exists($version_class)) {
            throw new InvalidVersionClassException("Class {$version_class} does not exist.");
        }

        $class = new \ReflectionClass($version_class);
        if (! $class->implementsInterface(SsimVersionContract::class)) {
            throw new InvalidContractException("Class {$version_class} must implement SsimVersionContract interface.");
        }

        /** @var SsimVersionContract $instance */
        $instance = $class->newInstance();
        $this->versionNumber = $instance::getName();

        return $this;
    }

    /**
     * Set the SSIM regex class to use for data extraction.
     *
     * @param string $regex_class The fully qualified class name of the regex implementation.
     * @return self Returns the current instance for method chaining.
     * @throws InvalidRegexClassException If the provided class does not exist.
     * @throws InvalidContractException If the provided class does not implement SsimRegexContract.
     */
    public function setRegex(string $regex_class): self
    {
        if (! class_exists($regex_class)) {
            throw new InvalidRegexClassException("Class {$regex_class} does not exist.");
        }

        $class = new \ReflectionClass($regex_class);
        if (! $class->implementsInterface(SsimRegexContract::class)) {
            throw new InvalidContractException("Class {$regex_class} must implement SsimRegexContract interface.");
        }

        /** @var SsimRegexContract $instance */
        $instance = $class->newInstance();
        $this->setRegexInstance($instance);

        return $this;
    }

    /**
     * Parse the SSIM data and retrieve the results.
     *
     * @return array<int, array<string, string>> An array of parsed flight data.
     */
    public function parse(): array
    {
        $output = [];
        $versionStr = (string) $this->versionNumber;

        foreach ($this->dataLines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || $trimmed[0] !== $versionStr) {
                continue;
            }
            $output = array_merge($output, $this->extractData($trimmed));
        }

        return $this->sort($output, 'departure_utc_datetime');
    }

    /**
     * Extract all relevant data from an SSIM line.
     *
     * @param string $data The SSIM line to process.
     * @return array<int, array<string, string>> An array of extracted data.
     * @throws InvalidInputException If the input data is empty.
     */
    private function extractData(string $data): array
    {
        if ($data === '') {
            throw new InvalidInputException("Data cannot be empty.");
        }

        $object = new \stdClass();

        foreach ($this->regexConstants as $name => $regex) {
            preg_match($regex, $data, $matches);
            if (count($matches) > 0 && ! in_array($regex, $this->hiddenAttributes, true)) {
                $key = strtolower($name);
                $object->{$key} = trim($matches[$key] ?? '') ?: null;
            }
            $data = preg_replace($regex, '', $data, 1) ?? $data;
        }

        return $this->formatResult($object);
    }

    /**
     * Format the extracted data into a structured array.
     *
     * @param object $data The extracted data object.
     * @return array<int, array<string, string>> An array of formatted flight data.
     */
    private function formatResult(object $data): array
    {
        $flights = [];
        $startDate = date('Y-m-d', strtotime($data->operation_start_date));
        $endDate = date('Y-m-d', strtotime($data->operation_end_date));
        $operation_dates = $this->getDatesInInterval($startDate, $endDate, str_split($data->operation_days_of_week));

        foreach ($operation_dates as $date) {
            $flights[] = $this->createFlight($data, $date);
        }

        return $flights;
    }

    /**
     * Create a flight array from the provided data and date.
     *
     * @param object $data An object containing flight-related data.
     * @param string $date The date of the flight operation in 'Y-m-d' format.
     * @return array<string, string> An associative array representing the flight.
     */
    private function createFlight(object $data, string $date): array
    {
        $local_departure = Carbon::parse($date . ' ' . $data->aircraft_departure_time . $data->utc_local_departure_time_variant);
        $local_arrival = Carbon::parse($date . ' ' . $data->aircraft_arrival_time . $data->utc_local_arrival_time_variant)->addDays(intval($data->date_variation));
        $utc_departure = (clone $local_departure)->setTimezone('UTC');
        $utc_arrival = (clone $local_arrival)->setTimezone('UTC');

        return [
            "uid" => $local_departure->format('YmdHis') . $this->parseFlightNumber($data->flight_number),
            "airline_designator" => $data->airline_designator,
            "service_type" => $data->service_type,
            "flight_number" => $data->flight_number,
            "departure_datetime" => $local_departure->format('Y-m-d H:i:s'),
            "arrival_datetime" => $local_arrival->format('Y-m-d H:i:s'),
            "departure_utc_datetime" => $utc_departure->format('Y-m-d H:i:s'),
            "arrival_utc_datetime" => $utc_arrival->format('Y-m-d H:i:s'),
            "departure_iata" => $data->departure_station,
            "arrival_iata" => $data->arrival_station,
            "aircraft_type" => $data->aircraft_type,
            "aircraft_configuration" => $data->aircraft_configuration_version,
        ];
    }

    /**
     * Get all dates in the given interval matching the specified days of the week.
     *
     * @param string $startDate The start date in 'Y-m-d' format.
     * @param string $endDate The end date in 'Y-m-d' format.
     * @param array<int, string> $daysOfWeek Days of the week (1-7 for Monday-Sunday).
     * @return array<int, string> An array of dates in 'Y-m-d' format.
     */
    private function getDatesInInterval(string $startDate, string $endDate, array $daysOfWeek = []): array
    {
        $begin = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $end->setTime(0, 0, 1);

        $result = [];

        /** @var \DateInterval $interval */
        $interval = \DateInterval::createFromDateString('1 day');

        $period = new \DatePeriod($begin, $interval, $end);
        foreach ($period as $dt) {
            $numOfDay = $dt->format('N');
            if (in_array($numOfDay, $daysOfWeek, true)) {
                $result[] = $dt->format('Y-m-d');
            }
        }

        return $result;
    }

    /**
     * Sort an array by a specified key.
     *
     * @param array<int, array<string, string>> $data The array to sort.
     * @param string $key The key to sort by.
     * @return array<int, array<string, string>> The sorted array.
     */
    private function sort(array $data, string $key): array
    {
        usort($data, fn (array $a, array $b): int => $a[$key] <=> $b[$key]);

        return $data;
    }

    /**
     * Parse the flight number and replace letters with numbers.
     *
     * @param string $flight_number The flight number to parse.
     * @return int The parsed flight number as an integer.
     */
    private function parseFlightNumber(string $flight_number): int
    {
        $letterToDigit = array_merge(range('A', 'Z'), range('a', 'z'));
        $digitMapping = array_merge(range(1, 26), range(1, 26));

        $result = strtr($flight_number, array_combine($letterToDigit, $digitMapping));

        return intval($result);
    }

    /**
     * Cache the regex constants and hidden attributes from a regex instance.
     */
    private function setRegexInstance(SsimRegexContract $instance): void
    {
        $this->regexInstance = $instance;
        $this->hiddenAttributes = $instance->getHiddenAttributes();

        $class = new \ReflectionClass($instance);
        $this->regexConstants = [];
        foreach ($class->getConstants() as $name => $value) {
            if (is_string($value)) {
                $this->regexConstants[$name] = $value;
            }
        }
    }
}
