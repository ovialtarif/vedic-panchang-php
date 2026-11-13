<?php

declare(strict_types=1);

/**
 * Build the runtime city catalogue from GeoNames cities5000.
 *
 * Usage: php scripts/build_city_catalogue.php
 * Requires PHP ZipArchive and internet access.
 */

$root = dirname(__DIR__);
$dataDirectory = $root . '/database/data';
$temporaryDirectory = sys_get_temp_dir() . '/vedic-panchang-geonames';

if (! extension_loaded('zip')) {
    fwrite(STDERR, "PHP ZipArchive extension is required.\n");
    exit(1);
}

if (! is_dir($temporaryDirectory) && ! mkdir($temporaryDirectory, 0775, true) && ! is_dir($temporaryDirectory)) {
    throw new RuntimeException('Unable to create temporary directory.');
}

function downloadFile(string $url, string $destination): void
{
    $context = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'vedic-panchang-php-city-catalogue/1.0']]);
    $contents = file_get_contents($url, false, $context);
    if ($contents === false) {
        throw new RuntimeException("Download failed: {$url}");
    }
    file_put_contents($destination, $contents);
}

function readTsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException("Cannot open {$path}");
    }

    $rows = [];
    while (($row = fgetcsv($handle, 0, "\t")) !== false) {
        $rows[] = $row;
    }
    fclose($handle);

    return $rows;
}

$archive = $temporaryDirectory . '/cities5000.zip';
downloadFile('https://download.geonames.org/export/dump/cities5000.zip', $archive);
$zip = new ZipArchive();
if ($zip->open($archive) !== true || ! $zip->extractTo($temporaryDirectory)) {
    throw new RuntimeException('Unable to extract GeoNames cities archive.');
}
$zip->close();

$countryFile = $temporaryDirectory . '/countryInfo.txt';
$adminFile = $temporaryDirectory . '/admin1CodesASCII.txt';
downloadFile('https://download.geonames.org/export/dump/countryInfo.txt', $countryFile);
downloadFile('https://download.geonames.org/export/dump/admin1CodesASCII.txt', $adminFile);

$countries = [];
foreach (readTsv($countryFile) as $row) {
    if (isset($row[0], $row[4]) && $row[0] !== '' && ! str_starts_with($row[0], '#')) {
        $countries[$row[0]] = $row[4];
    }
}

$regions = [];
foreach (readTsv($adminFile) as $row) {
    if (isset($row[0], $row[1])) {
        $regions[$row[0]] = $row[1];
    }
}

$source = $temporaryDirectory . '/cities5000.txt';
$input = fopen($source, 'rb');
if ($input === false) {
    throw new RuntimeException('GeoNames cities5000.txt was not found after extraction.');
}

if (! is_dir($dataDirectory) && ! mkdir($dataDirectory, 0775, true) && ! is_dir($dataDirectory)) {
    throw new RuntimeException('Unable to create database/data directory.');
}

$output = fopen($dataDirectory . '/cities.csv', 'wb');
if ($output === false) {
    throw new RuntimeException('Unable to create cities.csv.');
}

fputcsv($output, ['id', 'name', 'ascii_name', 'region', 'region_code', 'country_code', 'country', 'latitude', 'longitude', 'elevation_m', 'timezone', 'population']);

$count = 0;
while (($row = fgetcsv($input, 0, "\t")) !== false) {
    if (count($row) < 19) {
        continue;
    }

    [$id, $name, $asciiName, , $latitude, $longitude, , , $countryCode, , $admin1] = $row;
    $regionCode = $countryCode . '.' . $admin1;
    $elevation = $row[15] !== '' ? $row[15] : $row[16];

    fputcsv($output, [
        $id,
        $name,
        $asciiName,
        $regions[$regionCode] ?? '',
        $admin1,
        $countryCode,
        $countries[$countryCode] ?? $countryCode,
        $latitude,
        $longitude,
        $elevation,
        $row[17] ?? '',
        $row[14] ?? 0,
    ]);
    $count++;
}

fclose($input);
fclose($output);

echo "Generated {$count} city records at database/data/cities.csv\n";
echo "Source: GeoNames cities5000 dataset (CC BY 4.0). See NOTICE for attribution.\n";
