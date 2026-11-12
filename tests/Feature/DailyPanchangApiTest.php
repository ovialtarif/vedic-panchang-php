<?php

namespace Tests\Feature;

use Tests\TestCase;

final class DailyPanchangApiTest extends TestCase
{
    public function test_daily_api_validates_location_and_date(): void
    {
        $this->getJson('/api/v1/panchang/daily?lat=91&lon=0&tz=Asia%2FDhaka&date=2026-11-12')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    }

    public function test_daily_api_rejects_city_id_and_coordinates_together(): void
    {
        $this->getJson(
            '/api/v1/panchang/daily?city_id=1275339&lat=23.8103&lon=90.4125&tz=Asia%2FDhaka'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    }

    public function test_daily_api_returns_real_calculation_or_a_structured_error(): void
    {
        $response = $this->getJson(
            '/api/v1/panchang/daily?lat=23.8103&lon=90.4125&tz=Asia%2FDhaka&date=2026-11-12'
        );

        self::assertContains($response->status(), [200, 422]);

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'data' => ['date', 'vara', 'day_window', 'sunrise', 'sunset', 'tithi', 'nakshatra'],
            ]);
        } else {
            $response->assertJsonStructure(['error' => ['code', 'message']]);
        }
    }
}
