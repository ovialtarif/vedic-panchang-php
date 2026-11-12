<?php

namespace App\Services\VedicPanchang\Contracts;

use DateTimeImmutable;

interface AstronomyEngine
{
    /**
     * Return geocentric apparent ecliptic longitudes in degrees.
     *
     * Implementations must use the selected ephemeris and Lahiri ayanamsha;
     * callers must never substitute guessed or hard-coded astronomical values.
     *
     * @return array{sun: float, moon: float}
     */
    public function solarLunarLongitude(DateTimeImmutable $instant): array;
}
