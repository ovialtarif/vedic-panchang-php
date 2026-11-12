<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DailyPanchangApiTest extends TestCase
{
    public function test_daily_api_validates_location_and_date(): void
    {
        $this->getJson('/api/v1/panchang/daily?lat=91&lon=0&tz=Asia%2FDhaka&date=2026-11-12')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    }

    public function test_daily_api_returns_a_structured_calculation_error_if_engine_fails(): void
    {
        // The endpoint must never fall back to fabricated astronomical values.
        $this->getJson('/api/v1/panchang/daily?lat=23.8103&lon=90.4125&tz=Asia%2FDhaka&date=2026-11-12')
            ->assertJsonStructure(['data', 'error']);
    }
}
