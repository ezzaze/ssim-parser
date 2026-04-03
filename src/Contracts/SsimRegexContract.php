<?php

declare(strict_types=1);

namespace Ezzaze\SsimParser\Contracts;

interface SsimRegexContract
{
    /** @return list<string> */
    public function getHiddenAttributes(): array;
}
