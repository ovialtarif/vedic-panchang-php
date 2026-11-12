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
        self::assertSame('shukla-dvitiya', $result['id']);
    }

    #[Test]
    public function it_calculates_nakshatra_and_pada(): void
    {
        $result = PanchangMath::nakshatra(0.0);

        self::assertSame(1, $result['number']);
        self::assertSame('Ashwini', $result['name']);
        self::assertSame(1, $result['pada']);
    }

    #[Test]
    public function it_names_all_30_tithis_correctly(): void
    {
        self::assertSame('Purnima', PanchangMath::tithi(0.0, 168.0)['name']);
        self::assertSame('Amavasya', PanchangMath::tithi(0.0, 348.0)['name']);
        self::assertSame('Krishna Pratipada', ucfirst('krishna').' '.PanchangMath::tithi(0.0, 180.0)['name']);
    }

    #[Test]
    public function it_calculates_yoga_karana_and_rashi(): void
    {
        self::assertSame('Vishkambha', PanchangMath::yoga(0.0, 0.0)['name']);
        self::assertSame('Kimstughna', PanchangMath::karana(0.0, 0.0)['name']);
        self::assertSame('Mesha', PanchangMath::rashi(0.0)['name']);
        self::assertSame('Meena', PanchangMath::rashi(359.99)['name']);
    }
}
