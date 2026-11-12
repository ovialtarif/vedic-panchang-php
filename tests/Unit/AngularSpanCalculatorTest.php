<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\AngularSpanCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AngularSpanCalculatorTest extends TestCase
{
    #[Test]
    public function it_finds_a_segment_span_across_zero_degrees(): void
    {
        // 350 degrees at JD 100, increasing 10 degrees per day.
        $angle = static fn (float $jd): float => fmod(350.0 + (($jd - 100.0) * 10.0), 360.0);

        $spans = AngularSpanCalculator::spansOver($angle, 12.0, 100.0, 101.1, 2.0);

        self::assertSame(29, $spans[0]->index);
        self::assertEqualsWithDelta(99.8, $spans[0]->startJulianDay, 1.0e-8);
        self::assertEqualsWithDelta(101.0, $spans[0]->endJulianDay, 1.0e-8);
        self::assertGreaterThanOrEqual(2, count($spans));
    }

    #[Test]
    public function it_finds_the_exact_crossing_by_bisection(): void
    {
        $angle = static fn (float $jd): float => fmod(($jd * 6.0) + 30.0, 360.0);

        $crossing = AngularSpanCalculator::crossingNear($angle, 60.0, 5.0, 6.0);

        self::assertEqualsWithDelta(5.0, $crossing, 1.0e-8);
    }

    #[Test]
    public function it_rejects_invalid_segment_sizes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AngularSpanCalculator::spansOver(static fn (float $jd): float => $jd, 7.0, 1.0, 2.0);
    }
}
