<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PanchangController
{
    public function daily(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'city_id' => ['required_without:lat', 'string', 'max:32'],
            'lat' => ['required_without:city_id', 'numeric', 'between:-90,90'],
            'lon' => ['required_with:lat', 'numeric', 'between:-180,180'],
            'tz' => ['required_with:lat', 'timezone'],
            'alt' => ['sometimes', 'numeric', 'between:-500,10000'],
            'profile' => ['sometimes', 'string', 'in:north-indian-smarta'],
        ]);

        return $this->enginePending('daily', $validated);
    }

    public function month(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'string', 'max:32'],
            'year' => ['required', 'integer', 'between:2020,2035'],
            'month' => ['required', 'integer', 'between:1,12'],
            'profile' => ['sometimes', 'string', 'in:north-indian-smarta'],
        ]);

        return $this->enginePending('month', $validated);
    }

    public function festivals(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'string', 'max:32'],
            'year' => ['required', 'integer', 'between:2020,2035'],
            'category' => ['sometimes', 'string', 'in:major'],
            'profile' => ['sometimes', 'string', 'in:north-indian-smarta'],
        ]);

        return $this->enginePending('festivals', $validated);
    }

    public function festival(Request $request, string $festival): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'string', 'max:32'],
            'year' => ['required', 'integer', 'between:2020,2035'],
            'profile' => ['sometimes', 'string', 'in:north-indian-smarta'],
        ]);

        return $this->enginePending('festival', ['festival' => $festival] + $validated);
    }

    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'string', 'max:120'],
            'country' => ['sometimes', 'string', 'size:2'],
        ]);

        return $this->enginePending('cities', $validated);
    }

    public function nearestCity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return $this->enginePending('nearest-city', $validated);
    }

    public function city(string $city): JsonResponse
    {
        return $this->enginePending('city', ['city_id' => $city]);
    }

    private function enginePending(string $operation, array $context): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'calculation_engine_not_ready',
                'message' => 'The PHP astronomy and Panchang calculation engine is being ported. No estimated or fabricated timings are returned.',
                'operation' => $operation,
            ],
            'context' => $context,
        ], 503);
    }
}
