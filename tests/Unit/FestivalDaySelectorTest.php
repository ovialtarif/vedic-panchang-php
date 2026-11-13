<?php

namespace Tests\Unit;

use App\Services\VedicPanchang\Festivals\FestivalDaySelector;
use InvalidArgumentException;
use Tests\TestCase;

final class FestivalDaySelectorTest extends TestCase
{
    public function test_it_selects_day_with_greatest_kala_coverage(): void
    {
        $frames = [
            ['date' => '2026-11-01', 'sunrise' => 1.0, 'next_sunrise' => 2.0, 'kalas' => ['pradosh' => [1.2, 1.4]]],
            ['date' => '2026-11-02', 'sunrise' => 2.0, 'next_sunrise' => 3.0, 'kalas' => ['pradosh' => [2.2, 2.4]]],
        ];

        self::assertSame('2026-11-02', (new FestivalDaySelector())->choose(1.3, 2.35, $frames, 'pradosh'));
    }

    public function test_first_and_second_tie_breaks_are_respected(): void
    {
        $frames = [
            ['date' => '2026-11-01', 'sunrise' => 1.0, 'next_sunrise' => 2.0, 'kalas' => ['sunrise' => [1.0, 1.0]]],
            ['date' => '2026-11-02', 'sunrise' => 2.0, 'next_sunrise' => 3.0, 'kalas' => ['sunrise' => [2.0, 2.0]]],
        ];

        $selector = new FestivalDaySelector();
        self::assertSame('2026-11-01', $selector->choose(0.5, 2.5, $frames, 'sunrise', 'first'));
        self::assertSame('2026-11-02', $selector->choose(0.5, 2.5, $frames, 'sunrise', 'second'));
    }

    public function test_bhadra_exclusion_is_removed_from_kala_coverage(): void
    {
        $selector = new FestivalDaySelector();

        self::assertEqualsWithDelta(
            0.5,
            $selector->coverage([1.0, 2.0], 1.0, 2.0, [[1.25, 1.75]]),
            0.000001,
        );
        self::assertSame(
            0.0,
            $selector->coverage([1.0, 1.0], 0.0, 2.0, [[0.5, 1.5]]),
        );
    }

    public function test_it_falls_back_to_udaya_tithi_then_home_day(): void
    {
        $selector = new FestivalDaySelector();
        $frames = [
            ['date' => '2026-11-01', 'sunrise' => 1.0, 'next_sunrise' => 2.0, 'kalas' => ['moonrise' => null]],
            ['date' => '2026-11-02', 'sunrise' => 2.0, 'next_sunrise' => 3.0, 'kalas' => ['moonrise' => null]],
        ];

        self::assertSame('2026-11-02', $selector->choose(1.5, 2.5, $frames, 'moonrise'));
        self::assertSame('2026-11-01', $selector->choose(1.2, 1.8, $frames, 'moonrise'));
    }

    public function test_invalid_tithi_range_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FestivalDaySelector())->choose(2.0, 1.0, [], 'sunrise');
    }
}
