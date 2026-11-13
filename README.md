# Vedic Panchang PHP

Laravel 13 / PHP 8.3+ port of the open-source [Vedic Panchang API](https://github.com/pradeepsattikar/vedic-panchang-api).

The target is a deterministic Panchang API for any date and location, including Tithi, Paksha, Nakshatra, Yoga, Karana, lunar masa, sunrise/sunset, moonrise/moonset, Rahu Kalam, Yamaganda, Gulika Kalam, Abhijit/Brahma Muhurta and festivals.

## Requirements

- PHP 8.3 or newer in the PHP 8.x line
- Composer 2
- Laravel 13
- PHP extensions: FFI (enabled), ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, tokenizer, xml
- Swiss Ephemeris FFI package (installed by Composer)
- PHP ZipArchive extension (only needed to generate the city catalogue)

## Local setup (Laragon / Windows)

```bash
git clone https://github.com/ovialtarif/vedic-panchang-php.git
cd vedic-panchang-php
composer install
copy .env.example .env
php artisan key:generate
php artisan serve
```

Enable the FFI extension in the PHP configuration used by Laragon. For local development, set `ffi.enable=true` in the active `php.ini`, restart Laragon, and verify with `php -m`.

The Swiss Ephemeris FFI package detects the operating system and loads its bundled native library. Its binary release is pinned to v1.1.1 (`SWISSEPH_LIBS_RELEASE=download/v1.1.1`) because the upstream `latest` release currently points to ephemeris data files rather than platform binaries. You can override it with `VP_SWISSEPH_LIBRARY_PATH`. Optional ephemeris data files can be configured with `VP_EPHEMERIS_PATH`; the current adapter uses the built-in Moshier ephemeris and does not require external data files.

## City catalogue

The city API reads `database/data/cities.csv`. Generate it from the GeoNames cities5000 dataset:

```bash
php scripts/build_city_catalogue.php
```

This downloads the GeoNames dataset and requires the PHP ZipArchive extension and internet access. The generated CSV is intentionally not committed to the repository. GeoNames data is licensed under CC BY 4.0; attribution is recorded in [NOTICE](NOTICE).

## API routes

- `GET /api/v1/meta`
- `GET /api/v1/panchang/daily` (coordinates or `city_id`)
- `GET /api/v1/panchang/month` (full daily Panchang entries for each date in a Gregorian month; requires city catalogue)
- `GET /api/v1/festivals` (festival engine port pending)
- `GET /api/v1/festivals/{festival}` (festival engine port pending)
- `GET /api/v1/cities?q=Dhaka&country=BD`
- `GET /api/v1/cities/nearest?lat=23.81&lon=90.41`
- `GET /api/v1/cities/{city}`

`GET /up` is Laravel's health endpoint.

## Calculation status

The Swiss Ephemeris adapter, angular span engine, Tithi/Nakshatra/Yoga/Karana/Rashi naming, Navagraha calculations, lunar month classification and day-timing calculations are implemented. City search, city detail, nearest-city lookup and city-ID daily calculation are implemented and require the generated GeoNames catalogue. Coordinate-based `/api/v1/panchang/daily` requests are wired to the calculator, but output has not yet passed full upstream fixture/parity validation. The monthly endpoint now composes a complete month from daily calculations and returns no partial calendar if any day fails. Festival calculation, festival detail, and festival-aware month annotations remain incomplete. Calculation exceptions return a generic HTTP 422 response rather than fabricated values.

## Licensing

This project is derived from Vedic Panchang API by Pradeep Sattikar and retains its **AGPL-3.0-or-later** licensing. Swiss Ephemeris is also distributed under Astrodienst's AGPL or professional-license terms. Review the upstream terms before commercial or closed-source deployment. See [LICENSE](LICENSE) and [NOTICE](NOTICE).
