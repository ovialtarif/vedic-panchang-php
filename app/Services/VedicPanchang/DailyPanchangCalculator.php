<?php

namespace App\Services\VedicPanchang;

use App\Services\VedicPanchang\Contracts\AstronomyEngine;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Daily Panchang orchestration for one location and local civil date.
 *
 * The Panchang window runs sunrise-to-next-sunrise where possible and falls
 * back to the local civil day when the Sun does not rise.
 */
final class DailyPanchangCalculator
{
    private const TITHI_DEGREES = 12.0;
    private const KARANA_DEGREES = 6.0;
    private const NAKSHATRA_DEGREES = 360.0 / 27.0;
    private const YOGA_DEGREES = 360.0 / 27.0;
    private const RASHI_DEGREES = 30.0;

    public function __construct(
        private readonly SwissEphemerisEngine $ephemeris,
        private readonly GrahaCalculator $grahas,
    ) {
    }

    /**
     * @param array{latitude:float,longitude:float,altitude_m?:float} $location
     * @return array<string, mixed>
     */
    public function calculate(
        string $date,
        array $location,
        string $timezone,
        string $profile = 'north-indian-smarta',
    ): array {
        if ($profile !== 'north-indian-smarta') {
            throw new InvalidArgumentException('Unknown calculation profile.');
        }

        $civil = JulianDay::localCivilDay($date, $timezone);
        $tz = new DateTimeZone($timezone);
        $civilWindow = [$civil['start_jd'], $civil['end_jd']];

        $sunrise = $this->celestialEvent('sun', 'rise', $civilWindow, $civilWindow[0], $location, $tz);
        $sunriseJd = $sunrise['_jd'];
        $sunset = $this->celestialEvent(
            'sun',
            'set',
            $civilWindow,
            $sunriseJd ?? $civilWindow[0],
            $location,
            $tz,
        );
        $sunsetJd = $sunset['_jd'];

        $nextSunriseJd = null;
        if ($sunriseJd !== null && $sunsetJd !== null) {
            $nextSunriseJd = $this->ephemeris->nextRiseOrSet(
                JulianDay::toDateTime($sunsetJd, $tz),
                'sun',
                'rise',
                $location,
            );
        }

        if ($nextSunriseJd !== null && $sunriseJd !== null && $nextSunriseJd > $sunriseJd
            && ($nextSunriseJd - $sunriseJd) < 1.5) {
            $basis = 'sunrise_to_next_sunrise';
            $windowJd = [$sunriseJd, $nextSunriseJd];
        } else {
            $basis = 'civil_day';
            $windowJd = $civilWindow;
        }

        $moonrise = $this->celestialEvent('moon', 'rise', $windowJd, $windowJd[0], $location, $tz);
        $moonset = $this->celestialEvent('moon', 'set', $windowJd, $windowJd[0], $location, $tz);

        $weekday = (int) $civil['start']->format('N') - 1;
        $anchor = $sunriseJd ?? $windowJd[0];
        $at = JulianDay::toDateTime($anchor, $tz);
        $elements = [
            'tithi' => $this->element(
                fn (float $jd): float => $this->elongation($jd),
                self::TITHI_DEGREES,
                $windowJd,
                $tz,
                'tithi',
            ),
            'nakshatra' => $this->element(
                fn (float $jd): float => $this->sunMoon($jd)['moon'],
                self::NAKSHATRA_DEGREES,
                $windowJd,
                $tz,
                'nakshatra',
            ),
            'yoga' => $this->element(
                fn (float $jd): float => PanchangMath::normalizeDegrees(
                    $this->sunMoon($jd)['sun'] + $this->sunMoon($jd)['moon']
                ),
                self::YOGA_DEGREES,
                $windowJd,
                $tz,
                'yoga',
            ),
            'karana' => $this->element(
                fn (float $jd): float => $this->elongation($jd),
                self::KARANA_DEGREES,
                $windowJd,
                $tz,
                'karana',
            ),
            'sun_sign' => $this->element(
                fn (float $jd): float => $this->sunMoon($jd)['sun'],
                self::RASHI_DEGREES,
                $windowJd,
                $tz,
                'rashi',
                33.0,
            ),
            'moon_sign' => $this->element(
                fn (float $jd): float => $this->sunMoon($jd)['moon'],
                self::RASHI_DEGREES,
                $windowJd,
                $tz,
                'rashi',
                3.0,
            ),
            'sun_nakshatra' => $this->element(
                fn (float $jd): float => $this->sunMoon($jd)['sun'],
                self::NAKSHATRA_DEGREES,
                $windowJd,
                $tz,
                'nakshatra',
                16.0,
            ),
        ];

        $sunAtAnchor = $this->sunMoon($anchor)['sun'];
        $moonAtAnchor = $this->sunMoon($anchor)['moon'];
        $masa = LunarMonthCalculator::calculate(
            fn (float $jd): float => $this->elongation($jd),
            fn (float $jd): float => $this->sunMoon($jd)['sun'],
            $anchor,
        );
        $grahas = $this->grahas->calculate($at);

        return [
            'date' => $date,
            'vara' => [
                'number' => (($weekday + 1) % 7) + 1,
                'id' => strtolower($this->varaName($weekday)),
                'name' => $this->varaName($weekday),
            ],
            'masa' => $masa,
            'day_window' => [
                'basis' => $basis,
                'start' => JulianDay::toDateTime($windowJd[0], $tz)->format(DATE_ATOM),
                'end' => JulianDay::toDateTime($windowJd[1], $tz)->format(DATE_ATOM),
            ],
            'sunrise' => $this->publicEvent($sunrise),
            'sunset' => $this->publicEvent($sunset),
            'moonrise' => $this->publicEvent($moonrise),
            'moonset' => $this->publicEvent($moonset),
            ...$elements,
            'timings' => DayTimingCalculator::calculate(
                $sunriseJd,
                $sunsetJd,
                $weekday,
                $tz,
                [2],
            ),
            'year' => $this->yearInfo($date, $masa['amanta']['number'], $sunAtAnchor),
            'grahas' => $grahas,
            'anchor_longitudes' => [
                'sun' => $sunAtAnchor,
                'moon' => $moonAtAnchor,
            ],
            'profile' => $profile,
        ];
    }

    private function sunMoon(float $jd): array
    {
        return $this->ephemeris->solarLunarLongitude(JulianDay::toDateTime($jd, new DateTimeZone('UTC')));
    }

    private function elongation(float $jd): float
    {
        $positions = $this->sunMoon($jd);

        return PanchangMath::normalizeDegrees($positions['moon'] - $positions['sun']);
    }

    private function element(
        callable $angle,
        float $segment,
        array $window,
        DateTimeZone $timezone,
        string $kind,
        float $searchDays = 2.0,
    ): array {
        $raw = AngularSpanCalculator::spansOver($angle, $segment, $window[0], $window[1], $searchDays);
        $spans = array_map(
            fn (RawSpan $span): array => $this->formatSpan($span, $timezone, $kind),
            $raw,
        );

        return [
            'at_sunrise' => $spans[0] ?? null,
            'spans' => $spans,
            'unavailable_reason' => null,
        ];
    }

    private function formatSpan(RawSpan $span, DateTimeZone $timezone, string $kind): array
    {
        $start = JulianDay::toDateTime($span->startJulianDay, $timezone);
        $end = JulianDay::toDateTime($span->endJulianDay, $timezone);
        $number = $span->index + 1;

        $details = match ($kind) {
            'tithi' => [
                'id' => (PanchangMath::tithi(0.0, $span->index * 12.0 + 1.0)['paksha']).'-'.strtolower(str_replace(' ', '-', PanchangMath::TITHI_NAMES[$span->index])),
                'name' => PanchangMath::TITHI_NAMES[$span->index],
                'paksha' => $span->index < 15 ? 'shukla' : 'krishna',
            ],
            'nakshatra' => [
                'id' => strtolower(str_replace(' ', '-', PanchangMath::NAKSHATRA_NAMES[$span->index])),
                'name' => PanchangMath::NAKSHATRA_NAMES[$span->index],
            ],
            'yoga' => [
                'id' => strtolower(str_replace(' ', '-', PanchangMath::YOGA_NAMES[$span->index])),
                'name' => PanchangMath::YOGA_NAMES[$span->index],
            ],
            'karana' => [
                'id' => strtolower(str_replace(' ', '-', PanchangMath::karanaName($span->index))),
                'name' => PanchangMath::karanaName($span->index),
            ],
            'rashi' => [
                'id' => strtolower(PanchangMath::RASHI_NAMES[$span->index]),
                'name' => PanchangMath::RASHI_NAMES[$span->index],
            ],
            default => throw new InvalidArgumentException('Unknown Panchang element.'),
        };

        return [
            'number' => $number,
            ...$details,
            'start' => $start->format(DATE_ATOM),
            'end' => $end->format(DATE_ATOM),
        ];
    }

    private function celestialEvent(
        string $body,
        string $event,
        array $window,
        float $searchFrom,
        array $location,
        DateTimeZone $timezone,
    ): array {
        $instant = JulianDay::toDateTime($searchFrom, new DateTimeZone('UTC'));
        $jd = $this->ephemeris->nextRiseOrSet($instant, $body, $event, $location);
        if ($jd === null) {
            return ['status' => 'unavailable', 'time' => null, 'next_time' => null,
                'reason' => "The {$body} does not {$event} at this location around this date", '_jd' => null];
        }

        if ($jd >= $window[0] && $jd < $window[1]) {
            $separation = null;
            if ($body === 'moon') {
                $separation = round(min($this->elongation($jd), 360.0 - $this->elongation($jd)), 2);
            }

            return ['status' => 'occurs', 'time' => JulianDay::toDateTime($jd, $timezone)->format(DATE_ATOM),
                'next_time' => null, 'reason' => null, 'moon_sun_separation_deg' => $separation, '_jd' => $jd];
        }

        return ['status' => 'no_event', 'time' => null,
            'next_time' => JulianDay::toDateTime($jd, $timezone)->format(DATE_ATOM),
            'reason' => "No {$body} {$event} in the requested window", '_jd' => null];
    }

    private function publicEvent(array $event): array
    {
        unset($event['_jd']);

        return $event;
    }

    private function varaName(int $weekday): string
    {
        return ['Somavara', 'Mangalavara', 'Budhavara', 'Guruvara', 'Shukravara', 'Shanivara', 'Ravivara'][$weekday];
    }

    private function yearInfo(string $date, int $amantaMonth, float $sunLongitude): array
    {
        $year = (int) substr($date, 0, 4);
        $month = (int) substr($date, 5, 2);
        $oldYear = $month <= 4 && $amantaMonth >= 10;
        $vikram = $year + ($oldYear ? 56 : 57);
        $ritu = ['Vasanta', 'Grishma', 'Varsha', 'Sharad', 'Hemanta', 'Shishira'][(int) floor(($amantaMonth - 1) / 2)];
        $uttarayana = in_array((int) floor($sunLongitude / 30.0), [9, 10, 11, 0, 1, 2], true);

        return [
            'vikram_samvat' => $vikram,
            'shaka_samvat' => $vikram - 135,
            'ritu' => $ritu,
            'ayana' => $uttarayana ? 'Uttarayana' : 'Dakshinayana',
        ];
    }
}
