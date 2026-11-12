<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

final class MetaController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => config('vedic-panchang.name'),
            'version' => config('vedic-panchang.version'),
            'engine' => config('vedic-panchang.engine'),
            'calculation_status' => 'port_in_progress',
            'supported_dates' => [
                'min' => config('vedic-panchang.min_date'),
                'max' => config('vedic-panchang.max_date'),
            ],
            'profiles' => ['north-indian-smarta'],
            'source' => 'https://github.com/pradeepsattikar/vedic-panchang-api',
            'license' => 'AGPL-3.0-or-later',
        ]);
    }
}
