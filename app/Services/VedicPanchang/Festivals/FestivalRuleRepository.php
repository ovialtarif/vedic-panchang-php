<?php

namespace App\Services\VedicPanchang\Festivals;

use InvalidArgumentException;
use RuntimeException;

final class FestivalRuleRepository
{
    private const MONTHS = [
        'chaitra', 'vaishakha', 'jyeshtha', 'ashadha', 'shravana', 'bhadrapada',
        'ashwin', 'kartika', 'margashirsha', 'pausha', 'magha', 'phalguna',
    ];

    private const SIGNS = [
        'mesha', 'vrishabha', 'mithuna', 'karka', 'simha', 'kanya',
        'tula', 'vrishchika', 'dhanu', 'makara', 'kumbha', 'meena',
    ];

    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    /** @return array<string, mixed> */
    public function load(string $profile = 'north-indian-smarta'): array
    {
        if (isset($this->cache[$profile])) {
            return $this->cache[$profile];
        }

        if (! preg_match('/^[a-z0-9-]{1,64}$/', $profile)) {
            throw new InvalidArgumentException('Invalid festival profile identifier.');
        }

        $path = base_path("src/vedic_panchang/rules/data/festivals/{$profile}.toml");
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Festival rule file is missing for profile: {$profile}");
        }

        $parsed = $this->parse(file_get_contents($path) ?: '');
        if (! isset($parsed['rule_version']) || ! is_string($parsed['rule_version'])) {
            throw new RuntimeException('Festival rule file must declare a rule_version.');
        }

        $lunar = $parsed['lunar'] ?? [];
        $solar = $parsed['solar'] ?? [];
        if (! is_array($lunar) || ! is_array($solar)) {
            throw new RuntimeException('Festival lunar and solar rules must be arrays.');
        }

        $ids = [];
        foreach ($lunar as $rule) {
            $this->validateLunar($rule);
            $ids[] = $rule['id'];
        }
        foreach ($solar as $rule) {
            $this->validateSolar($rule);
            $ids[] = $rule['id'];
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw new RuntimeException('Festival rule identifiers must be unique.');
        }

        return $this->cache[$profile] = [
            'id' => $profile,
            'rule_version' => $parsed['rule_version'],
            'lunar' => $lunar,
            'solar' => $solar,
        ];
    }

    /** @return array<string, mixed> */
    private function parse(string $source): array
    {
        $result = [];
        $section = null;

        foreach (preg_split('/\R/', $source) ?: [] as $lineNumber => $line) {
            $line = trim($this->stripComment($line));
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\[\[(lunar|solar)\]\]$/', $line, $match)) {
                $section = $match[1];
                $result[$section] ??= [];
                $result[$section][] = [];
                continue;
            }

            if (! preg_match('/^([a-z_]+)\s*=\s*(.+)$/u', $line, $match)) {
                throw new RuntimeException('Invalid festival TOML at line '.($lineNumber + 1).'.');
            }

            $key = $match[1];
            $value = $this->parseValue(trim($match[2]));
            if ($section === null) {
                $result[$key] = $value;
            } else {
                $index = array_key_last($result[$section]);
                $result[$section][$index][$key] = $value;
            }
        }

        return $result;
    }

    private function stripComment(string $line): string
    {
        $quoted = false;
        $escaped = false;
        for ($i = 0, $length = strlen($line); $i < $length; $i++) {
            $char = $line[$i];
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($char === '\\' && $quoted) {
                $escaped = true;
                continue;
            }
            if ($char === '"') {
                $quoted = ! $quoted;
            } elseif ($char === '#' && ! $quoted) {
                return substr($line, 0, $i);
            }
        }

        return $line;
    }

    private function parseValue(string $value): mixed
    {
        if ($value === 'true' || $value === 'false') {
            return $value === 'true';
        }
        if (preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }
        if (str_starts_with($value, '"') || str_starts_with($value, '[')) {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (is_string($decoded) || is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Unsupported or invalid value in festival TOML.');
    }

    /** @param array<string, mixed> $rule */
    private function validateLunar(array $rule): void
    {
        foreach (['id', 'name', 'tithi', 'kala'] as $required) {
            if (! array_key_exists($required, $rule)) {
                throw new RuntimeException("Lunar festival rule is missing {$required}.");
            }
        }
        if (! is_string($rule['id']) || ! preg_match('/^[a-z0-9-]+$/', $rule['id'])
            || ! is_string($rule['name']) || ! is_int($rule['tithi'])
            || $rule['tithi'] < 1 || $rule['tithi'] > 30) {
            throw new RuntimeException('Lunar festival rule contains invalid identity, name, or Tithi.');
        }
        if (! in_array($rule['kala'], ['sunrise', 'sunset', 'arunodaya', 'purvahna', 'madhyahna', 'aparahna', 'pradosh', 'nishita', 'moonrise'], true)) {
            throw new RuntimeException("Unknown kala in lunar rule {$rule['id']}.");
        }
        if (isset($rule['amanta_month']) && $rule['amanta_month'] !== 'any'
            && ! in_array($rule['amanta_month'], self::MONTHS, true)) {
            throw new RuntimeException("Unknown Amanta month in lunar rule {$rule['id']}.");
        }
        if (isset($rule['skip_months'])) {
            foreach ($rule['skip_months'] as $month) {
                if (! in_array($month, self::MONTHS, true)) {
                    throw new RuntimeException("Unknown skipped month in lunar rule {$rule['id']}.");
                }
            }
        }
        if (isset($rule['category']) && ! in_array($rule['category'], ['major', 'observance'], true)) {
            throw new RuntimeException("Unknown category in lunar rule {$rule['id']}.");
        }
        if (isset($rule['sade_teen_muhurat']) && ! in_array($rule['sade_teen_muhurat'], ['full', 'half'], true)) {
            throw new RuntimeException("Invalid Sade Teen Muhurat value in {$rule['id']}.");
        }
    }

    /** @param array<string, mixed> $rule */
    private function validateSolar(array $rule): void
    {
        foreach (['id', 'name'] as $required) {
            if (! isset($rule[$required]) || ! is_string($rule[$required])) {
                throw new RuntimeException("Solar festival rule is missing {$required}.");
            }
        }
        if (isset($rule['sign']) && $rule['sign'] !== 'any' && ! in_array($rule['sign'], self::SIGNS, true)) {
            throw new RuntimeException("Unknown solar sign in rule {$rule['id']}.");
        }
    }
}
