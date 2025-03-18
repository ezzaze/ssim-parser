<?php

namespace Ezzaze\SsimParser;

use Carbon\Carbon;
use Ezzaze\SsimParser\Contracts\{SsimRegexContract, SsimVersionContract};
use Ezzaze\SsimParser\Exceptions\{EmptyDataSourceException, InvalidContractException, InvalidInputException, InvalidRegexClassException, InvalidVersionClassException};
use Ezzaze\SsimParser\Versions\{Version3};
use Ezzaze\SsimParser\Regexes\Version3 as Version3Regex;

class SsimParser
{
    protected string $rawData;
    protected $version;
    protected $regex;
    protected string $ssimVersion;
    protected array $dataLines = [];
    protected array $recordTypesSupported = [];

    /**
     * Constructor for SsimParser.
     *
     * Initializes the parser with default or provided implementations of SsimVersionContract and SsimRegexContract.
     *
     * @param SsimVersionContract $version The version implementation to use (default: Version3).
     * @param SsimRegexContract $regex The regex implementation to use (default: Version3Regex).
     */
    public function __construct(SsimVersionContract $version = new Version3, SsimRegexContract $regex = new Version3Regex)
    {
        $this->recordTypesSupported = $this->getSupportedVersions();
        $this->setVersion(get_class($version));
        $this->setRegex(get_class($regex));
    }

    /**
     * Get a list of supported SSIM versions.
     *
     * This method scans all declared classes and identifies those that implement the SsimVersionContract interface.
     *
     * @return array An array of supported SSIM version names.
     */
    protected function getSupportedVersions(): array
    {
        static $versions = null;

        if ($versions === null) {
            $versions = [];
            foreach (get_declared_classes() as $className) {
                if (in_array(SsimVersionContract::class, class_implements($className))) {
                    $versions[] = (new \ReflectionClass($className))->newInstance()->getName();
                }
            }
        }

        return $versions;
    }

    /**
     * Load SSIM data from a provided source.
     *
     * The source can be either a file path or raw data. The data is split into lines for further processing.
     *
     * @param string $source The file path or raw data string.
     * @return self Returns the current instance for method chaining.
     * @throws EmptyDataSourceException If the provided source is empty.
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
     * Set the SSIM version to use for parsing.
     *
     * @param string $version_class The class name of the version implementation.
     * @return self Returns the current instance for method chaining.
     * @throws InvalidVersionClassException If the provided class does not exist.
     * @throws InvalidContractException If the provided class does not implement SsimVersionContract.
     */
    public function setVersion(string $version_class): self
    {
        if (!class_exists($version_class)) {
            throw new InvalidVersionClassException("Class {$version_class} does not exist.");
        }

        $class = new \ReflectionClass($version_class);
        if (!$class->implementsInterface(SsimVersionContract::class)) {
            throw new InvalidContractException("Class {$version_class} must implement SsimVersionContract interface.");
        }
        $this->version = $class->newInstance()::getName();

        return $this;
    }

    /**
     * Set the SSIM regex class to use for data extraction.
     *
     * @param string $regex_class The class name of the regex implementation.
     * @return self Returns the current instance for method chaining.
     * @throws InvalidRegexClassException If the provided class does not exist.
     * @throws InvalidContractException If the provided class does not implement SsimRegexContract.
     */
    public function setRegex(string $regex_class): self
    {
        if (!class_exists($regex_class)) {
            throw new InvalidRegexClassException("Class {$regex_class} does not exist.");
        }

        $class = new \ReflectionClass($regex_class);
        if (!$class->implementsInterface(SsimRegexContract::class)) {
            throw new InvalidContractException("Class {$regex_class} must implement SsimRegexContract interface.");
        }
        $this->regex = $regex_class;

        return $this;
    }

    /**
     * Parse the SSIM data and retrieve the results.
     *
     * This method processes each line of the loaded data and extracts relevant information using the configured regex.
     *
     * @return array An array of parsed flight data.
     */
    public function parse(): array
    {
        $output = [];
        foreach ($this->dataLines as $line) {
            if (empty($line) || str_split(trim($line))[0] != $this->version) {
                continue;
            }
            $output = array_merge_recursive($output, $this->extractData(trim($line)));
        }

        return $this->sort($output, 'departure_utc_datetime');
    }

    /**
     * Extract all relevant data from an SSIM line.
     *
     * This method uses the configured regex to extract data from a single SSIM line.
     *
     * @param string $data The SSIM line to process.
     * @return array An array of extracted data.
     * @throws InvalidInputException If the input data is empty.
     */
    private function extractData(string $data): array
    {
        if (empty($data)) {
            throw new InvalidInputException("Data cannot be empty.");
        }

        $object = (object)[];
        $class = new \ReflectionClass($this->regex);
        foreach ($class->getConstants() as $name => $regex) {
            preg_match($regex, $data, $matches);
            if (sizeof($matches) > 0 && !in_array($regex, $class->newInstance()->getHiddenAttributes())) {
                $object->{strtolower($name)} = trim($matches[strtolower($name)]) ?? null;
            }
            $data = preg_replace($regex, '', $data, 1);
        }

        return $this->formatResult($object);
    }

    /**
     * Format the extracted data into a structured array.
     *
     * This method processes the extracted data and generates flight information for each operation date.
     *
     * @param object $data The extracted data object.
     * @return array An array of formatted flight data.
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
     * This method constructs a flight array containing details such as departure and arrival times,
     * flight number, airline designator, and other relevant information.
     *
     * @param object $data An object containing flight-related data.
     * @param string $date The date of the flight operation in 'Y-m-d' format.
     * @return array An associative array representing the flight.
     * @throws \InvalidArgumentException If the input data is invalid or missing required fields.
     */
    private function createFlight(object $data, string $date): array
    {
        $local_departure = Carbon::parse($date . ' ' . $data->aircraft_departure_time . $data->utc_local_departure_time_variant);
        $local_arrival = Carbon::parse($date . ' ' . $data->aircraft_arrival_time . $data->utc_local_arrival_time_variant)->addDays(intVal($data->date_variation));
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
            "aicraft_type" => $data->aircraft_type,
            "aicraft_configuration" => $data->aircraft_configuration_version,
        ];
    }

    /**
     * Get all dates in the given interval matching the specified days of the week.
     *
     * @param string $startDate The start date in 'Y-m-d' format.
     * @param string $endDate The end date in 'Y-m-d' format.
     * @param array $daysOfWeek An array of days of the week (e.g., [1, 2, 3] for Monday, Tuesday, Wednesday).
     * @return array An array of dates in 'Y-m-d' format.
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
     * Sort an array by a specified key.
     *
     * @param array $data The array to sort.
     * @param string $key The key to sort by.
     * @return array The sorted array.
     */
    private function sort(array $data, string $key): array
    {
        usort($data, fn($a, $b) => $a[$key] <=> $b[$key]);
        return $data;
    }

    /**
     * Parse the flight number and replace letters with numbers.
     *
     * This method is used to handle delayed flights (e.g., "123D").
     *
     * @param string $flight_number The flight number to parse.
     * @return int The parsed flight number as an integer.
     * @throws InvalidInputException If the flight number contains invalid characters.
     */
    private function parseFlightNumber(string $flight_number): int
    {
        $letterToDigit = array_merge(range('A', 'Z'), range('a', 'z'));
        $digitMapping = array_merge(range(1, 26), range(1, 26));

        $result = strtr($flight_number, array_combine($letterToDigit, $digitMapping));
        return intval($result);
    }
}