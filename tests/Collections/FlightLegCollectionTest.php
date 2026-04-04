<?php

declare(strict_types=1);

use Ezzaze\SsimParser\Collections\FlightLegCollection;
use Ezzaze\SsimParser\DTOs\FlightLeg;

function makeFlight(string $flightNumber, string $departureUtc, string $departureIata = 'EVN'): FlightLeg
{
    return FlightLeg::fromArray([
        'uid' => $departureUtc . $flightNumber,
        'airline_designator' => 'ME',
        'service_type' => 'J',
        'flight_number' => $flightNumber,
        'departure_datetime' => $departureUtc,
        'arrival_datetime' => $departureUtc,
        'departure_utc_datetime' => $departureUtc,
        'arrival_utc_datetime' => $departureUtc,
        'departure_iata' => $departureIata,
        'arrival_iata' => 'HRG',
        'aircraft_type' => '320',
        'aircraft_configuration' => 'Y174',
    ]);
}

it('can be created empty', function () {
    $collection = new FlightLegCollection();

    expect($collection)->toBeEmpty();
    expect($collection->count())->toBe(0);
    expect($collection->first())->toBeNull();
    expect($collection->last())->toBeNull();
    expect($collection->isEmpty())->toBeTrue();
});

it('can be created from FlightLeg array', function () {
    $flights = [
        makeFlight('501', '2022-07-03 03:00:00'),
        makeFlight('502', '2022-07-03 07:00:00'),
    ];

    $collection = new FlightLegCollection($flights);

    expect($collection->count())->toBe(2);
    expect($collection->first()->flightNumber)->toBe('501');
    expect($collection->last()->flightNumber)->toBe('502');
    expect($collection->isEmpty())->toBeFalse();
});

it('can be created from legacy arrays', function () {
    $arrays = [
        [
            'uid' => '1', 'airline_designator' => 'ME', 'service_type' => 'J',
            'flight_number' => '501', 'departure_datetime' => '2022-07-03 07:00:00',
            'arrival_datetime' => '2022-07-03 08:40:00', 'departure_utc_datetime' => '2022-07-03 03:00:00',
            'arrival_utc_datetime' => '2022-07-03 06:40:00', 'departure_iata' => 'EVN',
            'arrival_iata' => 'HRG', 'aircraft_type' => '320', 'aircraft_configuration' => 'Y174',
        ],
    ];

    $collection = FlightLegCollection::fromArrays($arrays);

    expect($collection->count())->toBe(1);
    expect($collection->first())->toBeInstanceOf(FlightLeg::class);
});

it('filters flights', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00', 'EVN'),
        makeFlight('502', '2022-07-03 07:00:00', 'HRG'),
        makeFlight('503', '2022-07-03 09:00:00', 'EVN'),
    ]);

    $filtered = $collection->filter(fn (FlightLeg $f) => $f->departureIata === 'EVN');

    expect($filtered->count())->toBe(2);
    expect($filtered->first()->flightNumber)->toBe('501');
    expect($filtered->last()->flightNumber)->toBe('503');
});

it('filter returns a new collection', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00'),
    ]);

    $filtered = $collection->filter(fn () => true);

    expect($filtered)->not->toBe($collection);
    expect($filtered->count())->toBe($collection->count());
});

it('sorts flights by callback', function () {
    $collection = new FlightLegCollection([
        makeFlight('503', '2022-07-03 09:00:00'),
        makeFlight('501', '2022-07-03 03:00:00'),
        makeFlight('502', '2022-07-03 07:00:00'),
    ]);

    $sorted = $collection->sortBy(
        fn (FlightLeg $a, FlightLeg $b) => $a->departureUtcDateTime <=> $b->departureUtcDateTime
    );

    expect($sorted->first()->flightNumber)->toBe('501');
    expect($sorted->last()->flightNumber)->toBe('503');
});

it('sort returns a new collection', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00'),
    ]);

    $sorted = $collection->sortBy(fn (FlightLeg $a, FlightLeg $b) => 0);

    expect($sorted)->not->toBe($collection);
});

it('gets flight by index', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00'),
        makeFlight('502', '2022-07-03 07:00:00'),
    ]);

    expect($collection->get(0)->flightNumber)->toBe('501');
    expect($collection->get(1)->flightNumber)->toBe('502');
    expect($collection->get(99))->toBeNull();
});

it('converts to array of arrays', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00'),
    ]);

    $arrays = $collection->toArray();

    expect($arrays)->toBeArray();
    expect($arrays[0])->toHaveKey('flight_number', '501');
    expect($arrays[0])->toHaveKey('service_type', 'J');
});

it('converts to JSON', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00'),
    ]);

    $json = $collection->toJson();
    $decoded = json_decode($json, true);

    expect($decoded)->toBeArray();
    expect($decoded[0]['flight_number'])->toBe('501');
});

it('implements JsonSerializable', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00'),
    ]);

    $json = json_encode($collection, JSON_THROW_ON_ERROR);
    $decoded = json_decode($json, true);

    expect($decoded[0]['airline_designator'])->toBe('ME');
});

it('is iterable with foreach', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00'),
        makeFlight('502', '2022-07-03 07:00:00'),
    ]);

    $numbers = [];
    foreach ($collection as $flight) {
        $numbers[] = $flight->flightNumber;
    }

    expect($numbers)->toBe(['501', '502']);
});

it('is countable', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00'),
        makeFlight('502', '2022-07-03 07:00:00'),
        makeFlight('503', '2022-07-03 09:00:00'),
    ]);

    expect(count($collection))->toBe(3);
});

it('returns all flights as DTOs', function () {
    $collection = new FlightLegCollection([
        makeFlight('501', '2022-07-03 03:00:00'),
        makeFlight('502', '2022-07-03 07:00:00'),
    ]);

    $all = $collection->all();

    expect($all)->toBeArray();
    expect($all)->toHaveCount(2);
    expect($all[0])->toBeInstanceOf(FlightLeg::class);
});
