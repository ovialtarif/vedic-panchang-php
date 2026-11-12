<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\GrahaCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GrahaCalculatorTest extends TestCase
{
    #[Test]
    public function it_returns_nine_normalized_navagraha_positions(): void
    {
        $result = app(GrahaCalculator::class)->calculate(
            new DateTimeImmutable('2026-01-01T12:00:00+00:00')
        );

        self::assertCount(9, $result['grahas']);
        self::assertSame('rahu', $result['grahas'][7]['id']);
        self::assertSame('ketu', $result['grahas'][8]['id']);

        foreach ($result['grahas'] as $graha) {
            self::assertGreaterThanOrEqual(0.0, $graha['longitude']);
            self::assertLessThan(360.0, $graha['longitude']);
            self::assertGreaterThanOrEqual(1, $graha['rashi_number']);
            self::assertLessThanOrEqual(12, $graha['rashi_number']);
            self::assertGreaterThanOrEqual(1, $graha['pada']);
            self::assertLessThanOrEqual(4, $graha['pada']);
        }
    }

    #[Test]
    public function ketu_is_exactly_opposite_rahu(): void
    {
        $result = app(GrahaCalculator::class)->calculate(
            new DateTimeImmutable('2026-01-01T12:00:00+00:00')
        );

        $rahu = $result['grahas'][7]['longitude'];
        $ketu = $result['grahas'][8]['longitude'];
        $difference = fmod($ketu - $rahu + 360.0, 360.0);

        self::assertEqualsWithDelta(180.0, $difference, 1.0e-8);
    }
}
