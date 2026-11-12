# Vedic Panchang PHP

Laravel 13 / PHP 8.3+ port of the open-source [Vedic Panchang API](https://github.com/pradeepsattikar/vedic-panchang-api).

The target is a deterministic Panchang API for any date and location, including Tithi, Paksha, Nakshatra, Yoga, Karana, lunar masa, sunrise/sunset, moonrise/moonset, Rahu Kalam, Yamaganda, Gulika Kalam, Abhijit/Brahma Muhurta and festivals.

## Requirements

- PHP 8.3+
- Composer 2
- Laravel 13
- MySQL 8+ (for persistent city catalogue / optional caching)
- PHP extensions: ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, tokenizer, xml

## Local setup (Laragon / Windows)

```bash
git clone https://github.com/ovialtarif/vedic-panchang-php.git
cd vedic-panchang-php
composer install
copy .env.example .env
php artisan key:generate
```

Configure database credentials in `.env`, then run:

```bash
php artisan migrate
php artisan serve
```

## API routes

- `GET /api/v1/meta`
- `GET /api/v1/panchang/daily`
- `GET /api/v1/panchang/month`
- `GET /api/v1/festivals`
- `GET /api/v1/festivals/{festival}`
- `GET /api/v1/cities`
- `GET /api/v1/cities/nearest`
- `GET /api/v1/cities/{city}`

`GET /up` is Laravel's health endpoint when enabled in the application skeleton.

> **Porting notice:** The HTTP skeleton and initial angular primitives are in place. Astronomy-dependent routes return HTTP 503 until the Swiss Ephemeris adapter and validated calculations are complete. The API never returns guessed sunrise, moonrise or Panchang timings.

## Compatibility goals

The port will preserve the upstream API's response semantics, ISO-8601 local timestamps with UTC offsets, explicit no-event statuses, tradition profiles and supported date range. See [PORTING.md](PORTING.md) for milestones.

## License and attribution

This project is derived from Vedic Panchang API by Pradeep Sattikar and retains its **AGPL-3.0-or-later** licensing. See [LICENSE](LICENSE) and [NOTICE](NOTICE). Swiss Ephemeris and GeoNames data retain their respective upstream licensing and attribution requirements.
