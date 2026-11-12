<?php

namespace App\Services\VedicPanchang;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * UTC Julian Day conversion helpers. Local civil-day boundaries are constructed
 * in the requested IANA timezone before conversion to UT.
 */
final class JulianDay
{
    private const UNIX_EPOCH_JD = 2440587.5;
    private const SECONDS_PER_DAY = 86400.0;

    public static function fromDateTime(DateTimeImmutable $instant): float
    {
        $seconds = (float) $instant->format('U')
            + ((int) $instant->format('u') / 1_000_000);

        return ($seconds / self::SECONDS_PER_DAY) + self::UNIX_EPOCH_JD;
    }

    public static function toDateTime(float $julianDayUt, DateTimeZone $timezone): DateTimeImmutable
    {
        if (! is_finite($julianDayUt)) {
            throw new InvalidArgumentException('Julian Day must be finite.');
        }

        $unixSeconds = (int) round(($julianDayUt - self::UNIX_EPOCH_JD) * self::SECONDS_PER_DAY);

        return (new DateTimeImmutable('@'.$unixSeconds))->setTimezone($timezone);
    }

    /**
     * @return array{start: DateTimeImmutable, end: DateTimeImmutable, start_jd: float, end_jd: float}
     */
    public static function localCivilDay(string $date, string $ianaTimezone): array
    {
        $timezone = new DateTimeZone($ianaTimezone);
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timezone);
        $errors = DateTimeImmutable::getLastErrors();

        if ($start === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $start->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Date must be a valid YYYY-MM-DD calendar date.');
        }

        $end = $start->modify('+1 day');

        return [
            'start' => $start,
            'end' => $end,
            'start_jd' => self::fromDateTime($start),
            'end_jd' => self::fromDateTime($end),
        ];
    }
}
