<?php

declare(strict_types=1);

namespace Ezzaze\SsimParser\Enums;

enum RecordType: int
{
    case Header = 1;
    case Carrier = 2;
    case FlightLeg = 3;
    case SegmentData = 4;
    case Trailer = 5;
}
