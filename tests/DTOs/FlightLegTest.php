<?php

declare(strict_types=1);

use Ezzaze\SsimParser\DTOs\FlightLeg;
use Ezzaze\SsimParser\Enums\ServiceType;

$sampleData = [
    'uid' => '20220703070000501',
    'airline_designator' => 'ME',
    'service_type' => 'J',
    'flight_number' => '501',
    'departure_datetime' => '2022-07-03 07:00:00',
    'arrival_datetime' => '2022-07-03 08:40:00',
    'departure_utc_datetime' => '2022-07-03 03:00:00',
    'arrival_utc_datetime' => '2022-07-03 06:40:00',
    'departure_iata' => 'EVN',
    'arrival_iata' => 'HRG',
    'aircraft_type' => '320',
    'aircraft_configuration' => 'Y174',
];

it('can be created from an array', function () use ($sampleData) {
    $flight = FlightLeg::fromArray($sampleData);

    expect($flight->uid)->toBe('20220703070000501');
    expect($flight->airlineDesignator)->toBe('ME');
    expect($flight->serviceType)->toBe(ServiceType::ScheduledPassenger);
    expect($flight->flightNumber)->toBe('501');
    expect($flight->departureDateTime)->toBe('2022-07-03 07:00:00');
    expect($flight->arrivalDateTime)->toBe('2022-07-03 08:40:00');
    expect($flight->departureUtcDateTime)->toBe('2022-07-03 03:00:00');
    expect($flight->arrivalUtcDateTime)->toBe('2022-07-03 06:40:00');
    expect($flight->departureIata)->toBe('EVN');
    expect($flight->arrivalIata)->toBe('HRG');
    expect($flight->aircraftType)->toBe('320');
    expect($flight->aircraftConfiguration)->toBe('Y174');
});

it('converts to array with snake_case keys', function () use ($sampleData) {
    $flight = FlightLeg::fromArray($sampleData);
    $array = $flight->toArray();

    expect($array)->toHaveKeys([
        'uid', 'airline_designator', 'service_type', 'flight_number',
        'departure_datetime', 'arrival_datetime',
        'departure_utc_datetime', 'arrival_utc_datetime',
        'departure_iata', 'arrival_iata',
        'aircraft_type', 'aircraft_configuration',
    ]);
    expect($array['service_type'])->toBe('J');
    expect($array['airline_designator'])->toBe('ME');
});

it('converts to JSON', function () use ($sampleData) {
    $flight = FlightLeg::fromArray($sampleData);
    $json = $flight->toJson();

    $decoded = json_decode($json, true);
    expect($decoded['airline_designator'])->toBe('ME');
    expect($decoded['service_type'])->toBe('J');
});

it('implements JsonSerializable', function () use ($sampleData) {
    $flight = FlightLeg::fromArray($sampleData);

    $encoded = json_encode($flight, JSON_THROW_ON_ERROR);
    $decoded = json_decode($encoded, true);

    expect($decoded['flight_number'])->toBe('501');
});

it('handles unknown service type as null', function () use ($sampleData) {
    $data = array_merge($sampleData, ['service_type' => 'Z']);
    $flight = FlightLeg::fromArray($data);

    expect($flight->serviceType)->toBeNull();
    expect($flight->toArray()['service_type'])->toBeNull();
});

it('is readonly and immutable', function () use ($sampleData) {
    $flight = FlightLeg::fromArray($sampleData);

    // Attempting to modify a readonly property throws an Error
    expect(fn () => $flight->airlineDesignator = 'XX')
        ->toThrow(Error::class);
});
