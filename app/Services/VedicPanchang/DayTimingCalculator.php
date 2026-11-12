<?php

namespace App\Services\VedicPanchang;

use DateTimeZone;
use InvalidArgumentException;

/**
 * Sunrise/sunset-derived Rahu Kalam, Yamaganda, Gulika, Abhijit and
 * Brahma Muhurta intervals for the North Indian Smarta profile.
 */
final class DayTimingCalculator
{
    private const DAY_PERIODS = [
        'rahu_kalam' => ['name' => 'Rahu Kalam', 'parts' => [2, 7, 5, 6, 4, 3, 8]],
        'yamaganda' => ['name' => 'Yamaganda', 'parts' => [4, 3, 2, 1, 7, 6, 5]],
        'gulika_kalam' => ['name' => 'Gulika Kalam', 'parts' => [6, 5, 4, 3, 2, 1, 7]],
    ];

    private const WEEKDAYS = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    ];

    /**
     * @return list<array{id:string,name:string,kind:string,applicable:bool,start:?string,end:?string,reason:?string}>
     */
    public static function calculate(
        ?float $sunriseJulianDay,
        ?float $sunsetJulianDay,
        int $weekday,
        DateTimeZone $timezone,
        array $abhijitExcludedWeekdays = [2],
    ): array {
        if ($weekday < 0 || $weekday > 6) {
            throw new InvalidArgumentException('Weekday must use ISO-like Monday=0 through Sunday=6 indexing.');
        }

        if ($sunriseJulianDay === null || $sunsetJulianDay === null) {
            $reason = 'Requires both sunrise and sunset on this date at this location.';
            $unavailable = [];
            foreach (self::DAY_PERIODS as $id => $period) {
                $unavailable[] = self::unavailable($id, $period['name'], 'inauspicious', $reason);
            }
            $unavailable[] = self::unavailable('abhijit_muhurta', 'Abhijit Muhurta', 'auspicious', $reason);
            $unavailable[] = self::unavailable('brahma_muhurta', 'Brahma Muhurta', 'auspicious', $reason);

            return $unavailable;
        }

        $dayLength = $sunsetJulianDay - $sunriseJulianDay;
        if ($dayLength <= 0.0 || $dayLength >= 1.0) {
            throw new InvalidArgumentException('Sunset must occur after sunrise and less than one day later.');
        }

        $results = [];
        $eighth = $dayLength / 8.0;
        foreach (self::DAY_PERIODS as $id => $period) {
            $part = $period['parts'][$weekday];
            $start = $sunriseJulianDay + (($part - 1) * $eighth);
            $results[] = self::interval(
                $id,
                $period['name'],
                'inauspicious',
                $start,
                $start + $eighth,
                $timezone,
            );
        }

        $abhijitLength = $dayLength / 15.0;
        $abhijitStart = $sunriseJulianDay + (7.0 * $abhijitLength);
        if (in_array($weekday, $abhijitExcludedWeekdays, true)) {
            $results[] = self::unavailable(
                'abhijit_muhurta',
                'Abhijit Muhurta',
                'auspicious',
                'Abhijit Muhurta is avoided on '.self::WEEKDAYS[$weekday].'s.',
            );
        } else {
            $results[] = self::interval(
                'abhijit_muhurta',
                'Abhijit Muhurta',
                'auspicious',
                $abhijitStart,
                $abhijitStart + $abhijitLength,
                $timezone,
            );
        }

        $nightMuhurta = (1.0 - $dayLength) / 15.0;
        $results[] = self::interval(
            'brahma_muhurta',
            'Brahma Muhurta',
            'auspicious',
            $sunriseJulianDay - (2.0 * $nightMuhurta),
            $sunriseJulianDay - $nightMuhurta,
            $timezone,
        );

        return $results;
    }

    private static function interval(
        string $id,
        string $name,
        string $kind,
        float $start,
        float $end,
        DateTimeZone $timezone,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'kind' => $kind,
            'applicable' => true,
            'start' => JulianDay::toDateTime($start, $timezone)->format(DATE_ATOM),
            'end' => JulianDay::toDateTime($end, $timezone)->format(DATE_ATOM),
            'reason' => null,
        ];
    }

    private static function unavailable(string $id, string $name, string $kind, string $reason): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'kind' => $kind,
            'applicable' => false,
            'start' => null,
            'end' => null,
            'reason' => $reason,
        ];
    }
}
