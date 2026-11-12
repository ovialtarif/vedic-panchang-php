<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\PanchangMath;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PanchangMathTest extends TestCase
{
    #[Test]
    public function it_normalizes_negative_and_overflow_degrees(): void
    {
        self::assertSame(350.0, PanchangMath::normalizeDegrees(-10.0));
        self::assertSame(10.0, PanchangMath::normalizeDegrees(370.0));
    }

    #[Test]
    public function it_calculates_tithi_from_sun_moon_elongation(): void
    {
        $result = PanchangMath::tithi(10.0, 25.0);

        self::assertSame(2, $result['number']);
        self::assertSame('shukla', $result['paksha']);
        self::assertSame('Dvitiya', $result['name']);
    }

    #[Test]
    public function it_calculates_nakshatra_and_pada(): void
    {
        $result = PanchangMath::nakshatra(0.0);

        self::assertSame(1, $result['number']);
        self::assertSame('Ashwini', $result['name']);
        self::assertSame(1, $result['pada']);
    }
}
