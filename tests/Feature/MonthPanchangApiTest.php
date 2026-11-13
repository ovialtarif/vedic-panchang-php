<?php

namespace Tests\Feature;

use Tests\TestCase;

final class MonthPanchangApiTest extends TestCase
{
    public function test_month_api_validates_year_and_month(): void
    {
        $this->getJson('/api/v1/panchang/month?city_id=1275339&year=2101&month=13')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['year', 'month']);
    }

    public function test_month_api_reports_missing_city_catalogue_without_fake_calendar_data(): void
    {
        $this->getJson('/api/v1/panchang/month?city_id=1275339&year=2026&month=11')
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'city_catalogue_unavailable');
    }
}
