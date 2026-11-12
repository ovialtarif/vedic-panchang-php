<?php

namespace App\Services\VedicPanchang;

final class PanchangMath
{
    public const TITHI_NAMES = [
        'Pratipada', 'Dvitiya', 'Tritiya', 'Chaturthi', 'Panchami',
        'Shashthi', 'Saptami', 'Ashtami', 'Navami', 'Dashami',
        'Ekadashi', 'Dwadashi', 'Trayodashi', 'Chaturdashi', 'Purnima',
    ];

    public const NAKSHATRA_NAMES = [
        'Ashwini', 'Bharani', 'Krittika', 'Rohini', 'Mrigashira',
        'Ardra', 'Punarvasu', 'Pushya', 'Ashlesha', 'Magha',
        'Purva Phalguni', 'Uttara Phalguni', 'Hasta', 'Chitra',
        'Swati', 'Vishakha', 'Anuradha', 'Jyeshtha', 'Mula',
        'Purva Ashadha', 'Uttara Ashadha', 'Shravana', 'Dhanishta',
        'Shatabhisha', 'Purva Bhadrapada', 'Uttara Bhadrapada', 'Revati',
    ];

    public static function normalizeDegrees(float $degrees): float
    {
        $normalized = fmod($degrees, 360.0);

        return $normalized < 0 ? $normalized + 360.0 : $normalized;
    }

    /**
     * Calculate the angular index for the 30 tithis.
     *
     * @return array{number: int, paksha: string, name: string}
     */
    public static function tithi(float $sunLongitude, float $moonLongitude): array
    {
        $elongation = self::normalizeDegrees($moonLongitude - $sunLongitude);
        $number = min(30, (int) floor($elongation / 12.0) + 1);
        $halfIndex = ($number - 1) % 15;

        return [
            'number' => $number,
            'paksha' => $number <= 15 ? 'shukla' : 'krishna',
            'name' => $number === 15
                ? 'Purnima'
                : ($number === 30 ? 'Amavasya' : self::TITHI_NAMES[$halfIndex]),
        ];
    }

    /**
     * Calculate the 27-fold nakshatra segment.
     *
     * @return array{number: int, name: string, pada: int}
     */
    public static function nakshatra(float $moonLongitude): array
    {
        $segment = 360.0 / 27.0;
        $longitude = self::normalizeDegrees($moonLongitude);
        $index = min(26, (int) floor($longitude / $segment));
        $within = fmod($longitude, $segment);

        return [
            'number' => $index + 1,
            'name' => self::NAKSHATRA_NAMES[$index],
            'pada' => min(4, (int) floor($within / ($segment / 4.0)) + 1),
        ];
    }
}
