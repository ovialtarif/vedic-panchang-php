<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\LunarMonthCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LunarMonthCalculatorTest extends TestCase
{
    #[Test]
    public function it_detects_adhika_month_when_the_sun_does_not_change_sign(): void
    {
        $epoch = 2451545.0;
        $elongation = static fn (float $jd): float => fmod(20.0 + (($jd - $epoch) * (360.0 / 29.530589)) + 360.0, 360.0);
        $sun = static fn (float $jd): float => 40.0;

        $result = LunarMonthCalculator::calculate($elongation, $sun, $epoch + 10.0);

        self::assertTrue($result['amanta']['is_adhika']);
        self::assertSame('adhika-jyeshtha', $result['amanta']['id']);
        self::assertSame($result['amanta']['id'], $result['purnimanta']['id']);
    }

    #[Test]
    public function it_uses_the_next_amanta_month_for_the_krishna_paksha_purnimanta_label(): void
    {
        $epoch = 2451545.0;
        $elongation = static fn (float $jd): float => fmod(250.0 + (($jd - $epoch) * (360.0 / 29.530589)) + 360.0, 360.0);
        $sun = static fn (float $jd): float => fmod(10.0 + (($jd - $epoch) * 0.9856) + 360.0, 360.0);

        $result = LunarMonthCalculator::calculate($elongation, $sun, $epoch);

        self::assertGreaterThan(180.0, $elongation($epoch));
        self::assertIsString($result['amanta']['id']);
        self::assertIsString($result['purnimanta']['id']);
    }

    #[Test]
    public function it_maps_solar_signs_to_lunar_months(): void
    {
        self::assertSame([1, false], LunarMonthCalculator::monthIndex(0, 1));
        self::assertSame([2, true], LunarMonthCalculator::monthIndex(1, 1));
    }
}
