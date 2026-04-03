<?php

declare(strict_types=1);

namespace Ezzaze\SsimParser\Versions;

use Ezzaze\SsimParser\Contracts\SsimVersionContract;

class Version3 implements SsimVersionContract
{
    public static function getName(): int
    {
        return 3;
    }
}
