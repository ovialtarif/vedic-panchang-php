<?php

namespace App\Services\VedicPanchang;

use App\Services\VedicPanchang\Contracts\AstronomyEngine;
use DateTimeImmutable;
use RuntimeException;
use SwissEph\FFI\SwissEphFFI;

final class SwissEphemerisEngine implements AstronomyEngine
{
    private const EPHEMERIS_MOSEPH = SwissEphFFI::SEFLG_MOSEPH;
    private const SIDEREAL_LAHIRI = SwissEphFFI::SE_SIDM_LAHIRI;
    private const JULIAN_UNIX_EPOCH = 2440587.5;

    private SwissEphFFI $swiss;

    public function __construct()
    {
        $libraryPath = config('vedic-panchang.swisseph_library_path');

        $this->swiss = new SwissEphFFI(
            is_string($libraryPath) && $libraryPath !== '' ? $libraryPath : null
        );

        $ephemerisPath = config('vedic-panchang.ephemeris_path');
        if (is_string($ephemerisPath) && $ephemerisPath !== '') {
            $this->swiss->swe_set_ephe_path($ephemerisPath);
        }
    }

    public function solarLunarLongitude(DateTimeImmutable $instant): array
    {
        $jd = self::julianDay($instant);
        $flags = self::EPHEMERIS_MOSEPH | SwissEphFFI::SEFLG_SIDEREAL;

        $this->swiss->swe_set_sid_mode(self::SIDEREAL_LAHIRI, 0.0, 0.0);

        return [
            'sun' => $this->calculateLongitude($jd, SwissEphFFI::SE_SUN, $flags),
            'moon' => $this->calculateLongitude($jd, SwissEphFFI::SE_MOON, $flags),
        ];
    }

    /**
     * @return array{longitude: float, speed: float} Sidereal longitude and degrees/day.
     */
    public function siderealLongitudeAndSpeed(DateTimeImmutable $instant, int $body): array
    {
        $this->swiss->swe_set_sid_mode(self::SIDEREAL_LAHIRI, 0.0, 0.0);

        $ffi = $this->swiss->getFFI();
        $values = $ffi->new('double[6]');
        $error = $ffi->new('char[256]');
        $flags = self::EPHEMERIS_MOSEPH
            | SwissEphFFI::SEFLG_SIDEREAL
            | SwissEphFFI::SEFLG_SPEED;

        $result = $this->swiss->swe_calc_ut(self::julianDay($instant), $body, $flags, $values, $error);

        if ($result < 0) {
            $message = $ffi->string($error);
            throw new RuntimeException('Swiss Ephemeris calculation error: '.($message ?: 'unknown error'));
        }

        return [
            'longitude' => PanchangMath::normalizeDegrees((float) $values[0]),
            'speed' => (float) $values[3],
        ];
    }

    /** @return array{longitude: float, distance_au: float} Tropical geocentric position. */
    public function tropicalPlanetPosition(DateTimeImmutable $instant, int $body): array
    {
        $ffi = $this->swiss->getFFI();
        $values = $ffi->new('double[6]');
        $error = $ffi->new('char[256]');
        $result = $this->swiss->swe_calc_ut(
            self::julianDay($instant),
            $body,
            self::EPHEMERIS_MOSEPH,
            $values,
            $error,
        );

        if ($result < 0) {
            $message = $ffi->string($error);
            throw new RuntimeException('Swiss Ephemeris calculation error: '.($message ?: 'unknown error'));
        }

        return [
            'longitude' => PanchangMath::normalizeDegrees((float) $values[0]),
            'distance_au' => (float) $values[2],
        ];
    }

    public function siderealAscendant(
        DateTimeImmutable $instant,
        float $latitude,
        float $longitude,
    ): float {
        $this->swiss->swe_set_sid_mode(self::SIDEREAL_LAHIRI, 0.0, 0.0);

        $ffi = $this->swiss->getFFI();
        $cusps = $ffi->new('double[13]');
        $ascmc = $ffi->new('double[10]');
        $status = $this->swiss->swe_houses_ex(
            self::julianDay($instant),
            SwissEphFFI::SEFLG_SIDEREAL,
            $latitude,
            $longitude,
            ord('W'),
            $cusps,
            $ascmc,
        );

        if ($status < 0) {
            throw new RuntimeException('Swiss Ephemeris could not calculate the ascendant for this location.');
        }

        return PanchangMath::normalizeDegrees((float) $ascmc[0]);
    }

    /**
     * Return the next rise/set Julian day (UT), or null when no event occurs.
     *
     * @param array{longitude: float, latitude: float, altitude_m?: float} $location
     */
    public function nextRiseOrSet(
        DateTimeImmutable $from,
        string $body,
        string $event,
        array $location
    ): ?float {
        $bodyId = match ($body) {
            'sun' => SwissEphFFI::SE_SUN,
            'moon' => SwissEphFFI::SE_MOON,
            default => throw new RuntimeException("Unsupported rise/set body: {$body}"),
        };

        $eventFlag = match ($event) {
            'rise' => SwissEphFFI::SE_CALC_RISE,
            'set' => SwissEphFFI::SE_CALC_SET,
            default => throw new RuntimeException("Unsupported rise/set event: {$event}"),
        };

        $bodyFlags = $body === 'moon'
            ? SwissEphFFI::SE_BIT_DISC_CENTER | SwissEphFFI::SE_BIT_NO_REFRACTION
            : 0;

        $ffi = $this->swiss->getFFI();
        $geo = $ffi->new('double[3]');
        $geo[0] = $location['longitude'];
        $geo[1] = $location['latitude'];
        $geo[2] = $location['altitude_m'] ?? 0.0;

        $times = $ffi->new('double[2]');
        $error = $ffi->new('char[256]');

        $result = $this->swiss->swe_rise_trans(
            self::julianDay($from),
            $bodyId,
            '',
            self::EPHEMERIS_MOSEPH,
            $eventFlag | $bodyFlags,
            $geo,
            1013.25,
            15.0,
            $times,
            $error
        );

        if ($result === -2) {
            return null;
        }

        if ($result < 0) {
            $message = $ffi->string($error);
            throw new RuntimeException('Swiss Ephemeris rise/set error: '.($message ?: 'unknown error'));
        }

        return (float) $times[0];
    }

    public static function julianDay(DateTimeImmutable $instant): float
    {
        return ((float) $instant->format('U') + ((int) $instant->format('u') / 1_000_000))
            / 86400.0 + self::JULIAN_UNIX_EPOCH;
    }

    private function calculateLongitude(float $jd, int $body, int $flags): float
    {
        $ffi = $this->swiss->getFFI();
        $values = $ffi->new('double[6]');
        $error = $ffi->new('char[256]');

        $result = $this->swiss->swe_calc_ut($jd, $body, $flags, $values, $error);

        if ($result < 0) {
            $message = $ffi->string($error);
            throw new RuntimeException('Swiss Ephemeris calculation error: '.($message ?: 'unknown error'));
        }

        return PanchangMath::normalizeDegrees((float) $values[0]);
    }
}
