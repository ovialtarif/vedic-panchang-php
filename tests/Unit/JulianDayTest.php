<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\JulianDay;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class JulianDayTest extends TestCase
{
    #[Test]
    public function it_converts_utc_instants_to_julian_day_and_back(): void
    {
        $instant = new DateTimeImmutable('2026-11-12T18:30:45+00:00');
        $jd = JulianDay::fromDateTime($instant);
        $roundTrip = JulianDay::toDateTime($jd, new DateTimeZone('UTC'));

        self::assertEquals($instant->getTimestamp(), $roundTrip->getTimestamp());
    }

    #[Test]
    public function it_uses_local_midnight_and_handles_daylight_saving_length(): void
    {
        $window = JulianDay::localCivilDay('2026-03-08', 'America/New_York');

        self::assertSame('2026-03-08T00:00:00-05:00', $window['start']->format(DATE_ATOM));
        self::assertSame('2026-03-09T00:00:00-04:00', $window['end']->format(DATE_ATOM));
        self::assertEqualsWithDelta(23.0 / 24.0, $window['end_jd'] - $window['start_jd'], 1.0e-8);
    }

    #[Test]
    public function it_rejects_invalid_civil_dates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        JulianDay::localCivilDay('2026-02-30', 'Asia/Dhaka');
    }
}
