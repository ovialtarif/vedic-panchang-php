<?php

namespace App\Services\VedicPanchang\Festivals;

use App\Services\VedicPanchang\AngularSpanCalculator;
use App\Services\VedicPanchang\Contracts\AstronomyEngine;
use App\Services\VedicPanchang\JulianDay;
use App\Services\VedicPanchang\LunarMonthCalculator;
use App\Services\VedicPanchang\PanchangMath;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Location-independent Tithi and Sankranti instants for a Gregorian year.
 * Civil observance-day assignment is handled separately by FestivalDaySelector.
 */
final class FestivalAstronomicalCalendar
{
    private const YEAR_MARGIN_DAYS = 45.0;
    private const MEAN_SUN_SPEED = 0.9856;

    public function __construct(private readonly AstronomyEngine $ephemeris)
    {
    }

    /**
     * @return list<array{
     *   number:int,start_jd:float,end_jd:float,
     *   amanta:array<string,mixed>,purnimanta:array<string,mixed>
     * }>
     */
    public function lunarTithis(int $year): array
    {
        $this->assertYear($year);
        [$start, $end] = $this->yearBounds($year);

        $spans = AngularSpanCalculator::spansOver(
            fn (float $jd): float => $this->elongation($jd),
            12.0,
            $start,
            $end,
        );

        $instances = [];
        foreach ($spans as $span) {
            $midpoint = ($span->startJulianDay + $span->endJulianDay) / 2.0;
            $months = LunarMonthCalculator::calculate(
                fn (float $jd): float => $this->elongation($jd),
                fn (float $jd): float => $this->solarLunar($jd)['sun'],
                $midpoint,
            );

            $instances[] = [
                'number' => $span->index + 1,
                'start_jd' => $span->startJulianDay,
                'end_jd' => $span->endJulianDay,
                'amanta' => $months['amanta'],
                'purnimanta' => $months['purnimanta'],
            ];
        }

        return $instances;
    }

    /**
     * @return list<array{sign:int,jd:float}>
     */
    public function sankrantis(int $year): array
    {
        $this->assertYear($year);
        [$start, $end] = $this->yearBounds($year);
        $sun = fn (float $jd): float => $this->solarLunar($jd)['sun'];
        $sign = (int) floor($sun($start) / 30.0);
        $jd = $start;
        $events = [];

        for ($count = 0; $count < 15; $count++) {
            $sign = ($sign + 1) % 12;
            $degreesToGo = PanchangMath::normalizeDegrees(($sign * 30.0) - $sun($jd));
            $jd = AngularSpanCalculator::crossingNear(
                $sun,
                $sign * 30.0,
                $jd + ($degreesToGo / self::MEAN_SUN_SPEED),
                self::MEAN_SUN_SPEED,
            );

            if ($jd > $end) {
                return $events;
            }

            if ($jd >= $start) {
                $events[] = ['sign' => $sign, 'jd' => $jd];
            }
        }

        throw new \RuntimeException('Sankranti search exceeded its annual safety limit.');
    }

    private function elongation(float $jd): float
    {
        $positions = $this->solarLunar($jd);

        return PanchangMath::normalizeDegrees($positions['moon'] - $positions['sun']);
    }

    /** @return array{sun:float,moon:float} */
    private function solarLunar(float $jd): array
    {
        return $this->ephemeris->solarLunarLongitude(
            JulianDay::toDateTime($jd, new DateTimeZone('UTC')),
        );
    }

    /** @return array{0:float,1:float} */
    private function yearBounds(int $year): array
    {
        $start = JulianDay::fromDateTime(new DateTimeImmutable("{$year}-01-01 00:00:00", new DateTimeZone('UTC')))
            - self::YEAR_MARGIN_DAYS;
        $end = JulianDay::fromDateTime(new DateTimeImmutable(($year + 1)."-01-01 00:00:00", new DateTimeZone('UTC')))
            + self::YEAR_MARGIN_DAYS;

        return [$start, $end];
    }

    private function assertYear(int $year): void
    {
        if ($year < 1900 || $year > 2100) {
            throw new InvalidArgumentException('Festival calendar year must be between 1900 and 2100.');
        }
    }
}
