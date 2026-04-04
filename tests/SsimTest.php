<?php

declare(strict_types=1);

use Ezzaze\SsimParser\Collections\FlightLegCollection;
use Ezzaze\SsimParser\DTOs\FlightLeg;
use Ezzaze\SsimParser\Exceptions\EmptyDataSourceException;
use Ezzaze\SsimParser\Exceptions\FileReadException;
use Ezzaze\SsimParser\Exceptions\InvalidContractException;
use Ezzaze\SsimParser\Exceptions\InvalidRegexClassException;
use Ezzaze\SsimParser\Exceptions\InvalidVersionClassException;
use Ezzaze\SsimParser\SsimParser;
use Ezzaze\SsimParser\Versions\Version3;

$data = "
    1AIRLINE STANDARD SCHEDULE DATA SET     1                                                                                                                                                      001000001
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    2LME  0008S22 27MAR2226MAR2315JUL22                             13JUN22C NetLine/Sched  2016.2.8                                                                                              0749000002
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    3 ME  5010101J03JUL2223OCT22      7 EVN07000700+0400  HRG08400840+0200  320                                                              ME  502                            Y174                00000003
    3 ME  5010201J06JUL2226OCT22  3     EVN09200920+0400  HRG11001100+0200  320                                                              ME  502                            Y174                00000004
    3 ME  5020101J03JUL2223OCT22      7 HRG09200920+0200  EVN14351435+0400  320                                                              ME  503                            Y174                00000005
    3 ME  5020201J06JUL2206JUL22  3     HRG11401140+0200  EVN16551655+0400  320                                                              ME  5032                           Y174                00000006
    3 ME  5020301J13JUL2226OCT22  3     HRG11401140+0200  EVN16551655+0400  320                                                              ME  503                            Y174                00000007
    3 ME  5030101J04JUL2229OCT221    6  EVN11001100+0400  SSH12251225+0200  320                                                              ME  504                            Y174                00000008
    3 ME  5030201J13JUL2226OCT22  3     EVN17551755+0400  SSH19201920+0200  320                                                              ME  504                            Y174                00000009
    3 ME  5040101J04JUL2224OCT221       SSH13051305+0200  EVN18051805+0400  320                                                              ME  5011                           Y174                00000010
    3 ME  5040201J09JUL2222OCT22     6  SSH13051305+0200  EVN18051805+0400  320                                                              ME  501                            Y174                00000011
    3 ME  5040301J13JUL2226OCT22  3     SSH20002000+0200  EVN01000100+0400  320                                                              ME  5032                           Y174                01000012
    3 ME  5040401J29OCT2229OCT22     6  SSH13051305+0200  EVN18051805+0400  320                                                                                                 Y174                00000013
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    5 ME                                                                                                                                                                                       000013E000014
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
    00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
";

// ── Parser: FlightLegCollection return ──────────────────────────────────

it('parse() returns a FlightLegCollection', function () use ($data) {
    $result = (new SsimParser(new Version3()))->load($data)->parse();

    expect($result)->toBeInstanceOf(FlightLegCollection::class);
    expect($result)->not->toBeEmpty();
    expect($result->first())->toBeInstanceOf(FlightLeg::class);
});

it('parse() result contains correct flight data', function () use ($data) {
    $result = (new SsimParser())->load($data)->parse();
    $first = $result->first();

    expect($first)->not->toBeNull();
    expect($first->airlineDesignator)->toBe('ME');
    expect($first->serviceType)->toBe(\Ezzaze\SsimParser\Enums\ServiceType::ScheduledPassenger);
    expect($first->aircraftType)->toBe('320');
    expect($first->aircraftConfiguration)->toBe('Y174');
    expect($first->departureIata)->toBeIn(['EVN', 'HRG', 'SSH']);
    expect($first->arrivalIata)->toBeIn(['EVN', 'HRG', 'SSH']);
});

it('parse() results are sorted by departure UTC datetime', function () use ($data) {
    $result = (new SsimParser())->load($data)->parse();

    $previous = null;
    foreach ($result as $flight) {
        if ($previous !== null) {
            expect($flight->departureUtcDateTime)->toBeGreaterThanOrEqual($previous->departureUtcDateTime);
        }
        $previous = $flight;
    }
});

it('parseToArray() returns legacy array format', function () use ($data) {
    $output = (new SsimParser())->load($data)->parseToArray();

    expect($output)->toBeArray();
    expect(count($output))->toBeGreaterThan(0);
    expect($output[0])->toHaveKeys([
        'uid',
        'airline_designator',
        'service_type',
        'flight_number',
        'departure_datetime',
        'arrival_datetime',
        'departure_utc_datetime',
        'arrival_utc_datetime',
        'departure_iata',
        'arrival_iata',
        'aircraft_type',
        'aircraft_configuration',
    ]);
});

// ── Parser: error handling ──────────────────────────────────────────────

it('throws invalid version class exception', function () use ($data) {
    $ssim = (new SsimParser())->setVersion('NonExistentVersionClass')->load($data);
    $ssim->parse();
})->throws(InvalidVersionClassException::class, 'Class NonExistentVersionClass does not exist.');

it('returns empty collection on invalid source', function () {
    $result = (new SsimParser())->load("this is not a valid SSIM string")->parse();

    expect($result)->toBeInstanceOf(FlightLegCollection::class);
    expect($result)->toBeEmpty();
    expect($result->first())->toBeNull();
});

it('throws invalid regex class exception', function () use ($data) {
    $ssim = (new SsimParser())->setRegex('NonExistentRegexClass')->load($data);
    $ssim->parse();
})->throws(InvalidRegexClassException::class, 'Class NonExistentRegexClass does not exist.');

it('throws invalid contract exception', function () use ($data) {
    $ssim = (new SsimParser())->setVersion(get_class(new class () {}))->load($data);
    $ssim->parse();
})->throws(InvalidContractException::class);

it('throws empty data source exception', function () {
    $ssim = new SsimParser();
    $ssim->load("");
})->throws(EmptyDataSourceException::class, 'Data source cannot be empty.');

it('throws file read exception for unreadable file', function () {
    $ssim = new SsimParser();
    $tempFile = tempnam(sys_get_temp_dir(), 'ssim_test_');
    if ($tempFile === false) {
        $this->markTestSkipped('Could not create temp file');
    }
    file_put_contents($tempFile, 'test');
    chmod($tempFile, 0000);

    try {
        $ssim->load($tempFile);
    } finally {
        chmod($tempFile, 0644);
        unlink($tempFile);
    }
})->throws(FileReadException::class)->skipOnWindows();

it('can load from a file path', function () use ($data) {
    $tempFile = tempnam(sys_get_temp_dir(), 'ssim_test_');
    if ($tempFile === false) {
        $this->markTestSkipped('Could not create temp file');
    }
    file_put_contents($tempFile, $data);

    try {
        $result = (new SsimParser())->load($tempFile)->parse();
        expect($result)->toBeInstanceOf(FlightLegCollection::class);
        expect($result)->not->toBeEmpty();
        expect($result->first())->toBeInstanceOf(FlightLeg::class);
    } finally {
        unlink($tempFile);
    }
});
