<?php

namespace App\Services\VedicPanchang;

final class PanchangMath
{
    public const TITHI_NAMES = [
        'Pratipada', 'Dwitiya', 'Tritiya', 'Chaturthi', 'Panchami',
        'Shashthi', 'Saptami', 'Ashtami', 'Navami', 'Dashami',
        'Ekadashi', 'Dwadashi', 'Trayodashi', 'Chaturdashi', 'Purnima',
        'Pratipada', 'Dwitiya', 'Tritiya', 'Chaturthi', 'Panchami',
        'Shashthi', 'Saptami', 'Ashtami', 'Navami', 'Dashami',
        'Ekadashi', 'Dwadashi', 'Trayodashi', 'Chaturdashi', 'Amavasya',
    ];

    public const NAKSHATRA_NAMES = [
        'Ashwini', 'Bharani', 'Krittika', 'Rohini', 'Mrigashira',
        'Ardra', 'Punarvasu', 'Pushya', 'Ashlesha', 'Magha',
        'Purva Phalguni', 'Uttara Phalguni', 'Hasta', 'Chitra',
        'Swati', 'Vishakha', 'Anuradha', 'Jyeshtha', 'Mula',
        'Purva Ashadha', 'Uttara Ashadha', 'Shravana', 'Dhanishtha',
        'Shatabhisha', 'Purva Bhadrapada', 'Uttara Bhadrapada', 'Revati',
    ];

    public const YOGA_NAMES = [
        'Vishkambha', 'Preeti', 'Ayushman', 'Saubhagya', 'Shobhana',
        'Atiganda', 'Sukarma', 'Dhriti', 'Shula', 'Ganda',
        'Vriddhi', 'Dhruva', 'Vyaghata', 'Harshana', 'Vajra',
        'Siddhi', 'Vyatipata', 'Variyan', 'Parigha', 'Shiva',
        'Siddha', 'Sadhya', 'Shubha', 'Shukla', 'Brahma',
        'Indra', 'Vaidhriti',
    ];

    public const RASHI_NAMES = [
        'Mesha', 'Vrishabha', 'Mithuna', 'Karka', 'Simha', 'Kanya',
        'Tula', 'Vrishchika', 'Dhanu', 'Makara', 'Kumbha', 'Meena',
    ];

    private const MOVABLE_KARANAS = [
        'Bava', 'Balava', 'Kaulava', 'Taitila', 'Garaja', 'Vanija', 'Vishti',
    ];

    private const FIXED_KARANAS_END = ['Shakuni', 'Chatushpada', 'Naga'];

    public static function normalizeDegrees(float $degrees): float
    {
        $normalized = fmod($degrees, 360.0);

        return $normalized < 0 ? $normalized + 360.0 : $normalized;
    }

    /** @return array{number: int, paksha: string, name: string, id: string} */
    public static function tithi(float $sunLongitude, float $moonLongitude): array
    {
        $elongation = self::normalizeDegrees($moonLongitude - $sunLongitude);
        $number = min(30, (int) floor($elongation / 12.0) + 1);
        $name = self::TITHI_NAMES[$number - 1];
        $paksha = $number <= 15 ? 'shukla' : 'krishna';

        return [
            'number' => $number,
            'paksha' => $paksha,
            'name' => $name,
            'id' => $paksha.'-'.strtolower(str_replace(' ', '-', $name)),
        ];
    }

    /** @return array{number: int, name: string, pada: int, id: string} */
    public static function nakshatra(float $moonLongitude): array
    {
        $segment = 360.0 / 27.0;
        $longitude = self::normalizeDegrees($moonLongitude);
        $index = min(26, (int) floor($longitude / $segment));
        $within = fmod($longitude, $segment);
        $name = self::NAKSHATRA_NAMES[$index];

        return [
            'number' => $index + 1,
            'name' => $name,
            'pada' => min(4, (int) floor($within / ($segment / 4.0)) + 1),
            'id' => strtolower(str_replace(' ', '-', $name)),
        ];
    }

    public static function yoga(float $sunLongitude, float $moonLongitude): array
    {
        $segment = 360.0 / 27.0;
        $index = min(26, (int) floor(self::normalizeDegrees($sunLongitude + $moonLongitude) / $segment));
        $name = self::YOGA_NAMES[$index];

        return ['number' => $index + 1, 'name' => $name, 'id' => strtolower($name)];
    }

    public static function karana(float $sunLongitude, float $moonLongitude): array
    {
        $halfTithi = (int) floor(self::normalizeDegrees($moonLongitude - $sunLongitude) / 6.0);
        $name = self::karanaName($halfTithi);

        return [
            'number' => $halfTithi + 1,
            'name' => $name,
            'id' => strtolower(str_replace(' ', '-', $name)),
        ];
    }

    public static function karanaName(int $index): string
    {
        if ($index < 0 || $index > 59) {
            throw new \InvalidArgumentException('Karana index must be between 0 and 59.');
        }

        if ($index === 0) {
            return 'Kimstughna';
        }

        if ($index >= 57) {
            return self::FIXED_KARANAS_END[$index - 57];
        }

        return self::MOVABLE_KARANAS[($index - 1) % 7];
    }

    public static function rashi(float $longitude): array
    {
        $index = min(11, (int) floor(self::normalizeDegrees($longitude) / 30.0));
        $name = self::RASHI_NAMES[$index];

        return ['number' => $index + 1, 'name' => $name, 'id' => strtolower($name)];
    }
}
