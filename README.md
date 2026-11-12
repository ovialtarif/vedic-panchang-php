# Vedic Panchang PHP

Laravel 13 / PHP 8.3+ port of the open-source [Vedic Panchang API](https://github.com/pradeepsattikar/vedic-panchang-api).

The target is a deterministic Panchang API for any date and location, including Tithi, Paksha, Nakshatra, Yoga, Karana, lunar masa, sunrise/sunset, moonrise/moonset, Rahu Kalam, Yamaganda, Gulika Kalam, Abhijit/Brahma Muhurta and festivals.

## Requirements

- PHP 8.3 or newer in the PHP 8.x line
- Composer 2
- Laravel 13
- PHP extensions: FFI (enabled), ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, tokenizer, xml
- Swiss Ephemeris FFI package (installed by Composer)

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

The Swiss Ephemeris FFI package detects Windows x64 and loads its bundled native library. You can override it with `VP_SWISSEPH_LIBRARY_PATH`. Optional ephemeris data files can be configured with `VP_EPHEMERIS_PATH`; the current adapter uses the built-in Moshier ephemeris and does not require external data files.

## API routes

- `GET /api/v1/meta`
- `GET /api/v1/panchang/daily`
- `GET /api/v1/panchang/month`
- `GET /api/v1/festivals`
- `GET /api/v1/festivals/{festival}`
- `GET /api/v1/cities`
- `GET /api/v1/cities/nearest`
- `GET /api/v1/cities/{city}`

`GET /up` is Laravel's health endpoint.

## Calculation status

The Swiss Ephemeris adapter provides Lahiri sidereal Sun/Moon longitudes and rise/set queries. The cyclic span engine now calculates segment boundary instants for Panchang angles; Tithi, Nakshatra, Yoga, Karana and Rashi naming/index rules are implemented; Navagraha positions, timezone-aware Julian Day helpers and Amanta/Purnimanta lunar month classification (including Adhika month detection) are available. Full daily orchestration, city catalogue, festival rules and response parity remain in progress. Until those are validated, daily Panchang endpoints intentionally return HTTP 503 rather than invented timings.

## Licensing

This project is derived from Vedic Panchang API by Pradeep Sattikar and retains its **AGPL-3.0-or-later** licensing. Swiss Ephemeris is also distributed under Astrodienst's AGPL or professional-license terms. Review the upstream terms before commercial or closed-source deployment. See [LICENSE](LICENSE) and [NOTICE](NOTICE).
