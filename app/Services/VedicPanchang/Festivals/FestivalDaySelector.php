<?php

namespace App\Services\VedicPanchang\Festivals;

use InvalidArgumentException;

/**
 * Applies the upstream festival Tithi/kala day-selection convention.
 *
 * Frames are ordered local civil days. All instants are Julian Day UT values.
 */
final class FestivalDaySelector
{
    /**
     * @param list<array{date: string, sunrise: float, next_sunrise: float, kalas: array<string, array{0: float, 1: float}|null>}> $frames
     * @param list<array{0: float, 1: float}> $excludedIntervals
     */
    public function choose(
        float $tithiStart,
        float $tithiEnd,
        array $frames,
        string $kala,
        string $tie = 'max_coverage',
        array $excludedIntervals = [],
    ): ?string {
        if ($tithiEnd <= $tithiStart) {
            throw new InvalidArgumentException('Tithi end must be after its start.');
        }
        if (! in_array($tie, ['first', 'second', 'max_coverage'], true)) {
            throw new InvalidArgumentException('Unsupported festival tie-break.');
        }

        $scored = [];
        $udaya = null;
        $home = null;

        foreach ($frames as $frame) {
            if ($frame['sunrise'] <= $tithiStart && $tithiStart < $frame['next_sunrise']) {
                $home = $frame['date'];
            }
            if ($udaya === null && $tithiStart <= $frame['sunrise'] && $frame['sunrise'] < $tithiEnd) {
                $udaya = $frame['date'];
            }

            $window = $frame['kalas'][$kala] ?? null;
            if ($window === null) {
                continue;
            }

            $share = $this->coverage($window, $tithiStart, $tithiEnd, $excludedIntervals);
            if ($share > 0.0) {
                $scored[] = ['date' => $frame['date'], 'share' => $share];
            }
        }

        if ($scored === []) {
            return $udaya ?? $home;
        }
        if (count($scored) === 1 || $tie === 'first') {
            return $scored[0]['date'];
        }
        if ($tie === 'second') {
            return $scored[1]['date'];
        }

        $best = $scored[0];
        foreach (array_slice($scored, 1) as $candidate) {
            if ($candidate['share'] > $best['share']) {
                $best = $candidate;
            }
        }

        return $best['date'];
    }

    /**
     * @param array{0: float, 1: float} $window
     * @param list<array{0: float, 1: float}> $excluded
     */
    public function coverage(array $window, float $start, float $end, array $excluded = []): float
    {
        [$windowStart, $windowEnd] = $window;

        if ($windowEnd <= $windowStart) {
            return ($start <= $windowStart && $windowStart < $end && ! $this->contains($excluded, $windowStart))
                ? 1.0
                : 0.0;
        }

        $prevailing = max(0.0, min($windowEnd, $end) - max($windowStart, $start));
        foreach ($excluded as [$excludedStart, $excludedEnd]) {
            $left = max($windowStart, $start, $excludedStart);
            $right = min($windowEnd, $end, $excludedEnd);
            if ($left < $right) {
                $prevailing -= $right - $left;
            }
        }

        return max(0.0, $prevailing) / ($windowEnd - $windowStart);
    }

    /** @param list<array{0: float, 1: float}> $intervals */
    private function contains(array $intervals, float $instant): bool
    {
        foreach ($intervals as [$start, $end]) {
            if ($start <= $instant && $instant < $end) {
                return true;
            }
        }

        return false;
    }
}
