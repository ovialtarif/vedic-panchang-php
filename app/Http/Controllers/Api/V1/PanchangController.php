<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Services\\VedicPanchang\\Cities\\CityRepository;
use App\\Services\\VedicPanchang\\DailyPanchangCalculator;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Validation\\ValidationException;
use RuntimeException;
use Throwable;

final class PanchangController
{
    public function daily(Request $request, DailyPanchangCalculator $calculator, CityRepository $cities): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'city_id' => ['sometimes', 'string', 'max:32'],
            'lat' => ['required_without:city_id', 'numeric', 'between:-90,90'],
            'lon' => ['required_with:lat', 'numeric', 'between:-180,180'],
            'tz' => ['required_with:lat', 'timezone'],
            'alt' => ['sometimes', 'numeric', 'between:-500,9000'],
            'profile' => ['sometimes', 'string', 'in:north-indian-smarta'],
        ]);

        if (isset($validated['city_id']) && $request->hasAny(['lat', 'lon', 'tz'])) {
            throw ValidationException::withMessages(['city_id' => 'Use either city_id or coordinates, not both.']);
        }

        if (isset($validated['city_id'])) {
            try {
                $city = $cities->find($validated['city_id']);
            } catch (RuntimeException $exception) {
                report($exception);
                return $this->catalogueUnavailable();
            }

            if ($city === null) {
                return response()->json(['error' => ['code' => 'city_not_found', 'message' => 'The requested city ID does not exist.']], 404);
            }

            $validated['lat'] = $city['latitude'];
            $validated['lon'] = $city['longitude'];
            $validated['tz'] = $city['timezone'] ?: 'UTC';
            $validated['alt'] = $city['elevation_m'] ?? 0;
        }

        $timezone = $validated['tz'];
        $date = $validated['date'] ?? (new DateTimeImmutable('now', new DateTimeZone($timezone)))->format('Y-m-d');

        try {
            $data = $calculator->calculate($date, [
                'latitude' => (float) $validated['lat'],
                'longitude' => (float) $validated['lon'],
                'altitude_m' => (float) ($validated['alt'] ?? 0.0),
            ], $timezone, $validated['profile'] ?? 'north-indian-smarta');

            return response()->json(['data' => $data]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['error' => [
                'code' => 'panchang_calculation_failed',
                'message' => 'The Panchang calculation could not be completed for this date and location.',
            ]], 422);
        }
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

    public function cities(Request $request, CityRepository $cities): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'string', 'max:120'],
            'country' => ['sometimes', 'string', 'size:2'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        try {
            return response()->json(['data' => $cities->search($validated['q'] ?? '', $validated['country'] ?? null, (int) ($validated['limit'] ?? 20))]);
        } catch (RuntimeException $exception) {
            report($exception);
            return $this->catalogueUnavailable();
        }
    }

    public function nearestCity(Request $request, CityRepository $cities): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            return response()->json(['data' => $cities->nearest((float) $validated['lat'], (float) $validated['lon'])]);
        } catch (RuntimeException $exception) {
            report($exception);
            return $this->catalogueUnavailable();
        }
    }

    public function city(string $city, CityRepository $cities): JsonResponse
    {
        try {
            $result = $cities->find($city);
        } catch (RuntimeException $exception) {
            report($exception);
            return $this->catalogueUnavailable();
        }

        return $result === null
            ? response()->json(['error' => ['code' => 'city_not_found', 'message' => 'The requested city ID does not exist.']], 404)
            : response()->json(['data' => $result]);
    }

    private function catalogueUnavailable(): JsonResponse
    {
        return response()->json(['error' => [
            'code' => 'city_catalogue_unavailable',
            'message' => 'The city catalogue is not installed. Import the GeoNames cities dataset to enable city lookup.',
        ]], 503);
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
