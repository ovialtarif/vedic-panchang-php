<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\Contracts\AstronomyEngine;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SwissEphemerisEngineTest extends TestCase
{
    #[Test]
    public function it_returns_normalized_lahiri_sidereal_sun_and_moon_longitudes(): void
    {
        $positions = app(AstronomyEngine::class)->solarLunarLongitude(
            new DateTimeImmutable('2026-01-01T12:00:00+00:00')
        );

        self::assertArrayHasKey('sun', $positions);
        self::assertArrayHasKey('moon', $positions);
        self::assertGreaterThanOrEqual(0.0, $positions['sun']);
        self::assertLessThan(360.0, $positions['sun']);
        self::assertGreaterThanOrEqual(0.0, $positions['moon']);
        self::assertLessThan(360.0, $positions['moon']);
    }
}
