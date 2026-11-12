<?php

namespace App\Services\VedicPanchang;

use InvalidArgumentException;
use RuntimeException;

/**
 * Finds the exact intervals during which a cyclic angular value occupies
 * equal segments of a 360-degree circle.
 *
 * The algorithm mirrors the upstream project's unwrapping + bisection method.
 * Angle callbacks receive Julian Day UT and must return degrees in [0, 360).
 */
final class AngularSpanCalculator
{
    private const BISECTION_STEPS = 40;
    private const DEFAULT_SEARCH_DAYS = 2.0;

    /**
     * @param callable(float): float $angleAt
     * @return list<RawSpan>
     */
    public static function spansOver(
        callable $angleAt,
        float $segmentDegrees,
        float $windowStart,
        float $windowEnd,
        float $searchDays = self::DEFAULT_SEARCH_DAYS,
    ): array {
        if ($segmentDegrees <= 0.0 || $segmentDegrees > 360.0
            || abs((360.0 / $segmentDegrees) - round(360.0 / $segmentDegrees)) > 1.0e-9) {
            throw new InvalidArgumentException('Segment size must divide 360 degrees exactly.');
        }

        if ($windowEnd <= $windowStart || $searchDays <= 0.0) {
            throw new InvalidArgumentException('Window end and search horizon must be greater than their starts.');
        }

        $count = (int) round(360.0 / $segmentDegrees);
        $angle = self::angle($angleAt, $windowStart);
        $index = min($count - 1, (int) floor($angle / $segmentDegrees));

        $start = self::crossing(
            $angleAt,
            $windowStart - $searchDays,
            $windowStart,
            $angle,
            $index * $segmentDegrees,
        );
        $end = self::crossing(
            $angleAt,
            $windowStart,
            $windowStart + $searchDays,
            $angle,
            ($index + 1) * $segmentDegrees,
        );

        $spans = [new RawSpan($index, $start, $end)];

        while ($spans[array_key_last($spans)]->endJulianDay < $windowEnd) {
            $previous = $spans[array_key_last($spans)];
            $probe = $previous->endJulianDay + 1.0e-6;
            $probeAngle = self::angle($angleAt, $probe);
            $nextIndex = min($count - 1, (int) floor($probeAngle / $segmentDegrees));
            $nextEnd = self::crossing(
                $angleAt,
                $probe,
                $probe + $searchDays,
                $probeAngle,
                ($nextIndex + 1) * $segmentDegrees,
            );

            if ($nextEnd <= $previous->endJulianDay) {
                throw new RuntimeException('Angular span search did not advance.');
            }

            $spans[] = new RawSpan($nextIndex, $previous->endJulianDay, $nextEnd);

            if (count($spans) > 1000) {
                throw new RuntimeException('Angular span search exceeded its safety limit.');
            }
        }

        return $spans;
    }

    /**
     * Find a crossing near a guess using a mean angular speed, then bisect it.
     *
     * @param callable(float): float $angleAt
     */
    public static function crossingNear(
        callable $angleAt,
        float $targetDegrees,
        float $guessJulianDay,
        float $meanDegreesPerDay,
        float $searchDays = self::DEFAULT_SEARCH_DAYS,
    ): float {
        if ($meanDegreesPerDay <= 0.0 || $searchDays <= 0.0) {
            throw new InvalidArgumentException('Mean angular speed and search horizon must be positive.');
        }

        $jd = $guessJulianDay;
        for ($i = 0; $i < 3; $i++) {
            $past = fmod(self::angle($angleAt, $jd) - $targetDegrees + 540.0, 360.0) - 180.0;
            $jd -= $past / $meanDegreesPerDay;
        }

        return self::crossing(
            $angleAt,
            $jd - $searchDays,
            $jd + $searchDays,
            $targetDegrees,
            $targetDegrees,
        );
    }

    private static function angle(callable $angleAt, float $jd): float
    {
        $angle = $angleAt($jd);
        if (! is_finite($angle)) {
            throw new RuntimeException('Angular callback returned a non-finite value.');
        }

        return PanchangMath::normalizeDegrees($angle);
    }

    private static function unwrapped(callable $angleAt, float $jd, float $reference): float
    {
        $delta = fmod(self::angle($angleAt, $jd) - $reference + 540.0, 360.0) - 180.0;

        return $reference + $delta;
    }

    private static function crossing(
        callable $angleAt,
        float $low,
        float $high,
        float $reference,
        float $target,
    ): float {
        if (! (self::unwrapped($angleAt, $low, $reference) < $target
            && $target <= self::unwrapped($angleAt, $high, $reference))) {
            throw new RuntimeException('Angular segment boundary is not bracketed; increase searchDays or inspect angle callback.');
        }

        for ($i = 0; $i < self::BISECTION_STEPS; $i++) {
            $middle = ($low + $high) / 2.0;
            if (self::unwrapped($angleAt, $middle, $reference) < $target) {
                $low = $middle;
            } else {
                $high = $middle;
            }
        }

        return ($low + $high) / 2.0;
    }
}
