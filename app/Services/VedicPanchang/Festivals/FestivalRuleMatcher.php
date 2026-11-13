<?php

namespace App\Services\VedicPanchang\Festivals;

use InvalidArgumentException;

final class FestivalRuleMatcher
{
    private const MONTHS = [
        'chaitra', 'vaishakha', 'jyeshtha', 'ashadha', 'shravana', 'bhadrapada',
        'ashwin', 'kartika', 'margashirsha', 'pausha', 'magha', 'phalguna',
    ];

    private const SIGNS = [
        'mesha', 'vrishabha', 'mithuna', 'karka', 'simha', 'kanya',
        'tula', 'vrishchika', 'dhanu', 'makara', 'kumbha', 'meena',
    ];

    /**
     * @param array<string, mixed> $rule
     * @param array<string, mixed> $tithi
     */
    public function matchesLunar(array $rule, array $tithi): bool
    {
        $month = $tithi['amanta'] ?? null;
        if (! is_array($month) || ! isset($tithi['number'], $month['id'], $month['is_adhika'])) {
            throw new InvalidArgumentException('Tithi instance is missing its number or Amanta month.');
        }

        if ((int) $tithi['number'] !== (int) ($rule['tithi'] ?? 0)) {
            return false;
        }

        $monthId = (string) $month['id'];
        $isAdhika = (bool) $month['is_adhika'];
        $monthIndex = str_starts_with($monthId, 'adhika-') ? substr($monthId, 7) : $monthId;

        $adhikaPolicy = $rule['adhika'] ?? (($rule['amanta_month'] ?? 'any') === 'any' ? 'both' : 'never');
        if ($adhikaPolicy === 'never' && $isAdhika) {
            return false;
        }
        if ($adhikaPolicy === 'only' && ! $isAdhika) {
            return false;
        }
        if (! in_array($adhikaPolicy, ['never', 'only', 'both'], true)) {
            throw new InvalidArgumentException('Unknown Adhika month policy.');
        }

        $requiredMonth = $rule['amanta_month'] ?? 'any';
        if ($requiredMonth !== 'any' && $requiredMonth !== $monthIndex) {
            return false;
        }

        $skipMonths = $rule['skip_months'] ?? [];
        return ! in_array($monthIndex, $skipMonths, true) || $isAdhika;
    }

    /**
     * @param array<string, mixed> $rule
     * @param array<string, mixed> $tithi
     */
    public function renderLunarName(array $rule, array $tithi, string $monthSystem = 'amanta'): string
    {
        if (! in_array($monthSystem, ['amanta', 'purnimanta'], true)) {
            throw new InvalidArgumentException('Month system must be amanta or purnimanta.');
        }

        $months = $tithi[$monthSystem] ?? null;
        if (! is_array($months) || ! isset($months['name'], $months['is_adhika'])) {
            throw new InvalidArgumentException('Tithi instance does not contain the requested month system.');
        }

        $monthName = ($months['is_adhika'] ? 'Adhika ' : '').$months['name'];
        return str_replace('{masa}', $monthName, (string) ($rule['name'] ?? ''));
    }

    /**
     * @param array<string, mixed> $rule
     * @param array{sign:int,jd:float} $event
     * @param list<int> $claimedSigns
     */
    public function matchesSolar(array $rule, array $event, array $claimedSigns = []): bool
    {
        $sign = $rule['sign'] ?? 'any';
        $eventSign = (int) ($event['sign'] ?? -1);

        if ($eventSign < 0 || $eventSign > 11) {
            throw new InvalidArgumentException('Sankranti event contains an invalid sign.');
        }

        if ($sign === 'any') {
            return ! in_array($eventSign, $claimedSigns, true);
        }

        $index = array_search($sign, self::SIGNS, true);
        if ($index === false) {
            throw new InvalidArgumentException('Solar rule contains an unknown sign.');
        }

        return $eventSign === $index;
    }

    public function signName(int $sign): string
    {
        if (! isset(self::SIGNS[$sign])) {
            throw new InvalidArgumentException('Solar sign index must be between 0 and 11.');
        }

        return ucfirst(self::SIGNS[$sign]);
    }

    public function renderSolarName(array $rule, int $sign): string
    {
        return str_replace('{sign}', $this->signName($sign), (string) ($rule['name'] ?? ''));
    }
}
