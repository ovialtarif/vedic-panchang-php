<?php

return [
    'name' => 'Vedic Panchang API',
    'version' => env('VP_API_VERSION', '0.2.0'),
    'engine' => 'Swiss Ephemeris (Moshier, Lahiri sidereal)',
    'min_date' => env('VP_MIN_DATE', '1900-01-01'),
    'max_date' => env('VP_MAX_DATE', '2100-12-31'),
    'default_profile' => env('VP_DEFAULT_PROFILE', 'north-indian-smarta'),
    'cache_ttl' => (int) env('VP_CACHE_TTL', 3600),
    'city_catalogue_path' => env('VP_CITIES_PATH', storage_path('app/vedic-panchang/cities.csv')),
    'ephemeris_path' => env('VP_EPHEMERIS_PATH'),
    'swisseph_library_path' => env('VP_SWISSEPH_LIBRARY_PATH'),
];
