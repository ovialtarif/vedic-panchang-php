<?php

namespace App\Services\VedicPanchang\Cities;

use Illuminate\Support\Str;
use RuntimeException;

final class CityRepository
{
    public function __construct(private readonly ?string $cataloguePath = null)
    {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $path = $this->cataloguePath ?? base_path('database/data/cities.csv');

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('City catalogue is missing. Run the city catalogue import command.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('City catalogue could not be opened.');
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                return [];
            }

            $cities = [];
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($header)) {
                    continue;
                }

                $city = array_combine($header, $row);
                if (! is_array($city) || ! isset($city['id'], $city['name'], $city['latitude'], $city['longitude'])) {
                    continue;
                }

                $city['id'] = (string) $city['id'];
                $city['latitude'] = (float) $city['latitude'];
                $city['longitude'] = (float) $city['longitude'];
                $city['population'] = isset($city['population']) ? (int) $city['population'] : 0;
                $city['elevation_m'] = isset($city['elevation_m']) && $city['elevation_m'] !== '' ? (float) $city['elevation_m'] : 0.0;
                $cities[] = $city;
            }

            return $cities;
        } finally {
            fclose($handle);
        }
    }

    /** @return list<array<string, mixed>> */
    public function search(string $query = '', ?string $country = null, int $limit = 20): array
    {
        $cities = $this->all();
        $needle = $this->normalize($query);
        $country = $country === null ? null : strtoupper($country);

        $matches = array_filter($cities, function (array $city) use ($needle, $country): bool {
            if ($country !== null && strtoupper((string) ($city['country_code'] ?? '')) !== $country) {
                return false;
            }

            if ($needle === '') {
                return true;
            }

            $name = $this->normalize((string) $city['name']);
            $ascii = $this->normalize((string) ($city['ascii_name'] ?? $city['name']));
            $region = $this->normalize((string) ($city['region'] ?? ''));

            return str_contains($name, $needle) || str_contains($ascii, $needle) || str_contains($region, $needle);
        });

        usort($matches, function (array $a, array $b) use ($needle): int {
            $aName = $this->normalize((string) $a['name']);
            $bName = $this->normalize((string) $b['name']);
            $aRank = $needle !== '' && $aName === $needle ? 0 : ($needle !== '' && str_starts_with($aName, $needle) ? 1 : 2);
            $bRank = $needle !== '' && $bName === $needle ? 0 : ($needle !== '' && str_starts_with($bName, $needle) ? 1 : 2);

            return ($aRank <=> $bRank) ?: ((int) $b['population'] <=> (int) $a['population']) ?: strcmp($aName, $bName);
        });

        return array_slice(array_values($matches), 0, max(1, min($limit, 100)));
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $city) {
            if ((string) $city['id'] === $id) {
                return $city;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function nearest(float $latitude, float $longitude): array
    {
        $nearest = null;
        $minimum = INF;

        foreach ($this->all() as $city) {
            $distance = $this->distanceKm($latitude, $longitude, (float) $city['latitude'], (float) $city['longitude']);
            if ($distance < $minimum) {
                $minimum = $distance;
                $nearest = $city;
            }
        }

        if ($nearest === null) {
            throw new RuntimeException('City catalogue contains no cities.');
        }

        $nearest['distance_km'] = round($minimum, 2);

        return $nearest;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(Str::ascii($value)));
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0088;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
