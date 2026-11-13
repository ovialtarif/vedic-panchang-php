<?php

namespace Tests\\Feature;

use App\\Services\\VedicPanchang\\Cities\\CityRepository;
use Tests\\TestCase;

final class CityApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            CityRepository::class,
            new CityRepository(base_path('tests/Fixtures/cities.csv'))
        );
    }

    public function test_city_search_endpoint_filters_by_country(): void
    {
        $this->getJson('/api/v1/cities?q=dhaka&country=BD')
            ->assertOk()
            ->assertJsonPath('data.0.id', '1275339')
            ->assertJsonPath('data.0.name', 'Dhaka');
    }

    public function test_nearest_city_endpoint_returns_distance(): void
    {
        $this->getJson('/api/v1/cities/nearest?lat=23.8103&lon=90.4125')
            ->assertOk()
            ->assertJsonPath('data.id', '1275339')
            ->assertJsonPath('data.distance_km', 0);
    }

    public function test_city_detail_endpoint_returns_404_for_unknown_id(): void
    {
        $this->getJson('/api/v1/cities/999999999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'city_not_found');
    }
}
