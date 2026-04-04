<?php

declare(strict_types=1);

namespace Ezzaze\SsimParser\DTOs;

use Ezzaze\SsimParser\Enums\ServiceType;

final readonly class FlightLeg implements \JsonSerializable
{
    public function __construct(
        public string $uid,
        public string $airlineDesignator,
        public ?ServiceType $serviceType,
        public string $flightNumber,
        public string $departureDateTime,
        public string $arrivalDateTime,
        public string $departureUtcDateTime,
        public string $arrivalUtcDateTime,
        public string $departureIata,
        public string $arrivalIata,
        public string $aircraftType,
        public string $aircraftConfiguration,
    ) {
    }

    /**
     * Create a FlightLeg from the legacy associative array format.
     *
     * @param array<string, string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            uid: $data['uid'],
            airlineDesignator: $data['airline_designator'],
            serviceType: ServiceType::tryFrom($data['service_type'] ?? ''),
            flightNumber: $data['flight_number'],
            departureDateTime: $data['departure_datetime'],
            arrivalDateTime: $data['arrival_datetime'],
            departureUtcDateTime: $data['departure_utc_datetime'],
            arrivalUtcDateTime: $data['arrival_utc_datetime'],
            departureIata: $data['departure_iata'],
            arrivalIata: $data['arrival_iata'],
            aircraftType: $data['aircraft_type'],
            aircraftConfiguration: $data['aircraft_configuration'],
        );
    }

    /**
     * Convert to an associative array.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'airline_designator' => $this->airlineDesignator,
            'service_type' => $this->serviceType?->value,
            'flight_number' => $this->flightNumber,
            'departure_datetime' => $this->departureDateTime,
            'arrival_datetime' => $this->arrivalDateTime,
            'departure_utc_datetime' => $this->departureUtcDateTime,
            'arrival_utc_datetime' => $this->arrivalUtcDateTime,
            'departure_iata' => $this->departureIata,
            'arrival_iata' => $this->arrivalIata,
            'aircraft_type' => $this->aircraftType,
            'aircraft_configuration' => $this->aircraftConfiguration,
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
