<?php

declare(strict_types=1);

namespace Ezzaze\SsimParser\Enums;

enum DayOfWeek: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    /**
     * Parse SSIM 7-character operation days string (e.g., "1..4.6.") into an array of DayOfWeek.
     *
     * Each position represents a day (1=Mon, 7=Sun). A digit means the day is active,
     * a space or non-digit means inactive.
     *
     * @return list<self>
     */
    public static function fromOperationDays(string $operationDays): array
    {
        $days = [];
        $chars = str_split($operationDays);

        foreach ($chars as $char) {
            $day = self::tryFrom((int) $char);
            if ($day !== null) {
                $days[] = $day;
            }
        }

        return $days;
    }
}
