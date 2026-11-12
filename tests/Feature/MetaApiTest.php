<?php

namespace Tests\Feature;

use Tests\TestCase;

final class MetaApiTest extends TestCase
{
    public function test_meta_endpoint_returns_project_and_engine_metadata(): void
    {
        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertJsonPath('name', 'Vedic Panchang API')
            ->assertJsonPath('license', 'AGPL-3.0-or-later');
    }

    public function test_daily_endpoint_does_not_return_unverified_calculations(): void
    {
        $this->getJson('/api/v1/panchang/daily?city_id=1275339')
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'calculation_engine_not_ready');
    }
}
