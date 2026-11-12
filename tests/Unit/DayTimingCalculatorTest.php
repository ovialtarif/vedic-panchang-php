<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\DayTimingCalculator;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DayTimingCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_eighth_part_periods_from_sunrise_and_sunset(): void
    {
        $sunrise = 2460000.25;
        $sunset = $sunrise + 0.5;

        $periods = DayTimingCalculator::calculate(
            $sunrise,
            $sunset,
            0,
            new DateTimeZone('Asia/Dhaka'),
        );

        self::assertCount(5, $periods);
        self::assertSame('rahu_kalam', $periods[0]['id']);
        self::assertEqualsWithDelta(0.5 / 8.0, (
            strtotime($periods[0]['end']) - strtotime($periods[0]['start'])
        ) / 86400.0, 1.0 / 86400.0);
        self::assertSame('abhijit_muhurta', $periods[3]['id']);
        self::assertSame('brahma_muhurta', $periods[4]['id']);
    }

    #[Test]
    public function it_marks_abhijit_unavailable_on_wednesday(): void
    {
        $periods = DayTimingCalculator::calculate(
            2460000.25,
            2460000.75,
            2,
            new DateTimeZone('UTC'),
        );

        self::assertFalse($periods[3]['applicable']);
        self::assertStringContainsString('Wednesday', $periods[3]['reason']);
    }

    #[Test]
    public function it_returns_explicit_unavailable_status_without_sunrise_or_sunset(): void
    {
        $periods = DayTimingCalculator::calculate(
            null,
            null,
            0,
            new DateTimeZone('UTC'),
        );

        self::assertCount(5, $periods);
        self::assertFalse($periods[0]['applicable']);
        self::assertNull($periods[0]['start']);
    }
}
