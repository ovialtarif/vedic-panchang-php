<?php

use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\PanchangController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/meta', MetaController::class);
    Route::get('/panchang/daily', [PanchangController::class, 'daily']);
    Route::get('/panchang/month', [PanchangController::class, 'month']);
    Route::get('/festivals', [PanchangController::class, 'festivals']);
    Route::get('/festivals/{festival}', [PanchangController::class, 'festival']);
    Route::get('/cities', [PanchangController::class, 'cities']);
    Route::get('/cities/nearest', [PanchangController::class, 'nearestCity']);
    Route::get('/cities/{city}', [PanchangController::class, 'city'])->whereNumber('city');
});
