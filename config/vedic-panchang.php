<?php

return [
    'name' => 'Vedic Panchang API',
    'version' => env('VP_API_VERSION', '0.1.0-port'),
    'engine' => 'PHP port (Swiss Ephemeris adapter pending)',
    'min_date' => env('VP_MIN_DATE', '2020-01-01'),
    'max_date' => env('VP_MAX_DATE', '2035-12-31'),
    'default_profile' => env('VP_DEFAULT_PROFILE', 'north-indian-smarta'),
    'cache_ttl' => (int) env('VP_CACHE_TTL', 3600),
    'city_catalogue_path' => env('VP_CITIES_PATH', storage_path('app/vedic-panchang/cities.csv')),
];
