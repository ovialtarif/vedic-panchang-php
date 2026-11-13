<?php

namespace Tests\Feature;

use App\Services\VedicPanchang\Cities\CityRepository;
use Tests\TestCase;

final class CityRepositoryTest extends TestCase
{
    private function repository(): CityRepository
    {
        return new CityRepository(base_path('tests/Fixtures/cities.csv'));
    }

    public function test_search_matches_city_name_and_country(): void
    {
        $results = $this->repository()->search('dhaka', 'BD');

        self::assertCount(1, $results);
        self::assertSame('1275339', $results[0]['id']);
    }

    public function test_nearest_city_uses_haversine_distance(): void
    {
        $city = $this->repository()->nearest(23.8103, 90.4125);

        self::assertSame('1275339', $city['id']);
        self::assertSame(0.0, $city['distance_km']);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        self::assertNull($this->repository()->find('999999999'));
    }
}
