<?php

namespace App\Providers;

use App\Services\VedicPanchang\Contracts\AstronomyEngine;
use App\Services\VedicPanchang\SwissEphemerisEngine;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AstronomyEngine::class, SwissEphemerisEngine::class);
    }

    public function boot(): void
    {
        //
    }
}
