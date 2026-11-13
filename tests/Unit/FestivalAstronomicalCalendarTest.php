<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\Contracts\AstronomyEngine;
use App\Services\VedicPanchang\Festivals\FestivalAstronomicalCalendar;
use App\Services\VedicPanchang\JulianDay;
use DateTimeImmutable;
use Tests\TestCase;

final class FestivalAstronomicalCalendarTest extends TestCase
{
    private function calendar(): FestivalAstronomicalCalendar
    {
        $engine = new class implements AstronomyEngine {
            public function solarLunarLongitude(DateTimeImmutable $instant): array
            {
                $days = JulianDay::fromDateTime($instant) - 2451545.0;
                $sun = fmod(280.0 + (0.9856 * $days), 360.0);
                $moon = fmod($sun + (12.1908 * $days) + 80.0, 360.0);

                return [
                    'sun' => $sun < 0 ? $sun + 360.0 : $sun,
                    'moon' => $moon < 0 ? $moon + 360.0 : $moon,
                ];
            }
        };

        return new FestivalAstronomicalCalendar($engine);
    }

    public function test_it_generates_a_full_year_of_ordered_tithi_instances(): void
    {
        $tithis = $this->calendar()->lunarTithis(2026);

        self::assertGreaterThan(350, count($tithis));
        self::assertLessThan(390, count($tithis));

        for ($i = 1, $count = count($tithis); $i < $count; $i++) {
            self::assertGreaterThan($tithis[$i - 1]['start_jd'], $tithis[$i]['start_jd']);
            self::assertGreaterThan($tithis[$i]['start_jd'], $tithis[$i - 1]['end_jd'] - 0.00001);
        }

        foreach ($tithis as $tithi) {
            self::assertGreaterThan(0, $tithi['number']);
            self::assertLessThanOrEqual(30, $tithi['number']);
            self::assertGreaterThan($tithi['start_jd'], $tithi['end_jd'] - 1.0);
            self::assertArrayHasKey('amanta', $tithi);
            self::assertArrayHasKey('purnimanta', $tithi);
        }
    }

    public function test_it_finds_solar_ingresses_across_the_year(): void
    {
        $events = $this->calendar()->sankrantis(2026);

        self::assertGreaterThanOrEqual(12, count($events));
        self::assertLessThanOrEqual(13, count($events));

        foreach ($events as $event) {
            self::assertGreaterThanOrEqual(0, $event['sign']);
            self::assertLessThanOrEqual(11, $event['sign']);
            self::assertGreaterThan(0, $event['jd']);
        }
    }

    public function test_it_rejects_years_outside_supported_range(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calendar()->lunarTithis(1800);
    }
}
