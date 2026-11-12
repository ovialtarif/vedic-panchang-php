<?php

namespace App\Services\VedicPanchang;

final readonly class RawSpan
{
    public function __construct(
        public int $index,
        public float $startJulianDay,
        public float $endJulianDay,
    ) {
        if ($index < 0 || $endJulianDay <= $startJulianDay) {
            throw new \InvalidArgumentException('A span must have a non-negative index and positive duration.');
        }
    }
}
