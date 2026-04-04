<?php

declare(strict_types=1);

use Ezzaze\SsimParser\Enums\DayOfWeek;
use Ezzaze\SsimParser\Enums\RecordType;
use Ezzaze\SsimParser\Enums\ServiceType;

// ── RecordType ──────────────────────────────────────────────────────────

it('has all five SSIM record types', function () {
    expect(RecordType::cases())->toHaveCount(5);
    expect(RecordType::Header->value)->toBe(1);
    expect(RecordType::Carrier->value)->toBe(2);
    expect(RecordType::FlightLeg->value)->toBe(3);
    expect(RecordType::SegmentData->value)->toBe(4);
    expect(RecordType::Trailer->value)->toBe(5);
});

it('can create RecordType from int', function () {
    expect(RecordType::from(3))->toBe(RecordType::FlightLeg);
    expect(RecordType::tryFrom(99))->toBeNull();
});

// ── ServiceType ─────────────────────────────────────────────────────────

it('maps common SSIM service type codes', function () {
    expect(ServiceType::from('J'))->toBe(ServiceType::ScheduledPassenger);
    expect(ServiceType::from('F'))->toBe(ServiceType::ScheduledCargo);
    expect(ServiceType::from('C'))->toBe(ServiceType::Charter);
});

it('returns null for unknown service type code', function () {
    expect(ServiceType::tryFromCode('Z'))->toBeNull();
    expect(ServiceType::tryFromCode(''))->toBeNull();
});

it('tryFromCode trims whitespace', function () {
    expect(ServiceType::tryFromCode(' J '))->toBe(ServiceType::ScheduledPassenger);
});

// ── DayOfWeek ───────────────────────────────────────────────────────────

it('has seven days', function () {
    expect(DayOfWeek::cases())->toHaveCount(7);
    expect(DayOfWeek::Monday->value)->toBe(1);
    expect(DayOfWeek::Sunday->value)->toBe(7);
});

it('parses SSIM operation days string', function () {
    // "      7" = only Sunday
    $days = DayOfWeek::fromOperationDays('      7');
    expect($days)->toHaveCount(1);
    expect($days[0])->toBe(DayOfWeek::Sunday);
});

it('parses full week operation days', function () {
    $days = DayOfWeek::fromOperationDays('1234567');
    expect($days)->toHaveCount(7);
    expect($days[0])->toBe(DayOfWeek::Monday);
    expect($days[6])->toBe(DayOfWeek::Sunday);
});

it('parses mixed operation days', function () {
    // "1  4 6 " = Monday, Thursday, Saturday
    $days = DayOfWeek::fromOperationDays('1  4 6 ');
    expect($days)->toHaveCount(3);
    expect($days[0])->toBe(DayOfWeek::Monday);
    expect($days[1])->toBe(DayOfWeek::Thursday);
    expect($days[2])->toBe(DayOfWeek::Saturday);
});

it('returns empty array for blank operation days', function () {
    $days = DayOfWeek::fromOperationDays('       ');
    expect($days)->toBeEmpty();
});
