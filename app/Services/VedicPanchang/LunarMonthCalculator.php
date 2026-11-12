<?php

namespace App\Services\VedicPanchang;

use InvalidArgumentException;

/**
 * Amanta and Purnimanta lunar month naming based on consecutive new moons
 * and the Sun's sidereal sign, matching the upstream rule definitions.
 */
final class LunarMonthCalculator
{
    public const MASA_NAMES = [
        'Chaitra', 'Vaishakha', 'Jyeshtha', 'Ashadha', 'Shravana', 'Bhadrapada',
        'Ashwin', 'Kartika', 'Margashirsha', 'Pausha', 'Magha', 'Phalguna',
    ];

    private const SYNODIC_MONTH_DAYS = 29.530589;
    private const MEAN_ELONGATION_SPEED = 360.0 / self::SYNODIC_MONTH_DAYS;

    /**
     * @param callable(float): float $elongationAt Julian Day UT -> Moon-Sun elongation.
     * @param callable(float): float $sunLongitudeAt Julian Day UT -> sidereal solar longitude.
     * @return array{amanta: array{number:int,id:string,name:string,is_adhika:bool},purnimanta:array{number:int,id:string,name:string,is_adhika:bool}}
     */
    public static function calculate(
        callable $elongationAt,
        callable $sunLongitudeAt,
        float $julianDay,
    ): array {
        if (! is_finite($julianDay)) {
            throw new InvalidArgumentException('Julian Day must be finite.');
        }

        [$index, $isAdhika, $monthEnd] = self::amantaMonth(
            $elongationAt,
            $sunLongitudeAt,
            $julianDay,
        );

        $amanta = self::formatMasa($index, $isAdhika);
        if (PanchangMath::normalizeDegrees($elongationAt($julianDay)) < 180.0) {
            return ['amanta' => $amanta, 'purnimanta' => $amanta];
        }

        [$nextIndex, $nextAdhika] = self::amantaMonth(
            $elongationAt,
            $sunLongitudeAt,
            $monthEnd + 1.0,
        );

        return [
            'amanta' => $amanta,
            'purnimanta' => self::formatMasa($nextIndex, $nextAdhika),
        ];
    }

    /** @return array{0:int,1:bool} */
    public static function monthIndex(int $sunSignAtStart, int $sunSignAtEnd): array
    {
        if ($sunSignAtStart < 0 || $sunSignAtStart > 11 || $sunSignAtEnd < 0 || $sunSignAtEnd > 11) {
            throw new InvalidArgumentException('Solar sign indexes must be between 0 and 11.');
        }

        if ($sunSignAtStart === $sunSignAtEnd) {
            return [($sunSignAtEnd + 1) % 12, true];
        }

        return [$sunSignAtEnd, false];
    }

    private static function newMoonBefore(callable $elongationAt, float $jd): float
    {
        $elongation = PanchangMath::normalizeDegrees($elongationAt($jd));
        $guess = $jd - ($elongation / self::MEAN_ELONGATION_SPEED);
        $newMoon = AngularSpanCalculator::crossingNear(
            $elongationAt,
            0.0,
            $guess,
            self::MEAN_ELONGATION_SPEED,
        );

        if ($newMoon > $jd) {
            $newMoon = AngularSpanCalculator::crossingNear(
                $elongationAt,
                0.0,
                $newMoon - self::SYNODIC_MONTH_DAYS,
                self::MEAN_ELONGATION_SPEED,
            );
        }

        return $newMoon;
    }

    /** @return array{0:int,1:bool,2:float} */
    private static function amantaMonth(
        callable $elongationAt,
        callable $sunLongitudeAt,
        float $jd,
    ): array {
        $start = self::newMoonBefore($elongationAt, $jd);
        $end = AngularSpanCalculator::crossingNear(
            $elongationAt,
            0.0,
            $start + self::SYNODIC_MONTH_DAYS,
            self::MEAN_ELONGATION_SPEED,
        );

        [$index, $isAdhika] = self::monthIndex(
            (int) floor(PanchangMath::normalizeDegrees($sunLongitudeAt($start)) / 30.0),
            (int) floor(PanchangMath::normalizeDegrees($sunLongitudeAt($end)) / 30.0),
        );

        return [$index, $isAdhika, $end];
    }

    /** @return array{number:int,id:string,name:string,is_adhika:bool} */
    private static function formatMasa(int $index, bool $isAdhika): array
    {
        $name = self::MASA_NAMES[$index];
        $id = ($isAdhika ? 'adhika-' : '').strtolower($name);

        return [
            'number' => $index + 1,
            'id' => $id,
            'name' => $name,
            'is_adhika' => $isAdhika,
        ];
    }
}
