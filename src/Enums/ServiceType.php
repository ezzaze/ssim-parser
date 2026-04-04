<?php

declare(strict_types=1);

namespace Ezzaze\SsimParser\Enums;

/**
 * IATA SSIM service type codes.
 *
 * @see IATA SSIM Chapter 7 — Service Type definitions
 */
enum ServiceType: string
{
    case ScheduledPassenger = 'J';
    case ScheduledCargo = 'F';
    case AdditionalFlightPassenger = 'G';
    case AdditionalFlightCargo = 'V';
    case Shuttle = 'S';
    case Charter = 'C';
    case CharterCargo = 'H';
    case ScheduledPassengerNormal = 'B';
    case Mail = 'M';
    case Other = 'O';

    /**
     * Try to create from a raw SSIM code, returning null for unknown codes.
     */
    public static function tryFromCode(string $code): ?self
    {
        return self::tryFrom(trim($code));
    }
}
