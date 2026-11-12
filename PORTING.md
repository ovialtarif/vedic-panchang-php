# PHP / Laravel Port Status

This repository began as a fork of [pradeepsattikar/vedic-panchang-api](https://github.com/pradeepsattikar/vedic-panchang-api).

## Porting principles

- Preserve the upstream AGPL-3.0-or-later license and attribution.
- Port the calculation rules; do not proxy to the Python API or invent timings.
- Keep astronomy behind an adapter so the ephemeris implementation can be tested independently.
- Match upstream JSON fields and time semantics before declaring endpoint parity.
- Verify against the upstream Drik Panchang reference fixtures and edge cases.

## Milestones

1. Laravel 13 API skeleton, request validation, metadata, and angular Panchang primitives.
2. Swiss Ephemeris PHP integration and Julian-day / timezone calculations.
3. Sunrise, sunset, moonrise, moonset and daily Tithi/Nakshatra/Yoga/Karana spans.
4. Rahu Kalam, Yamaganda, Gulika, Abhijit and Brahma Muhurta.
5. Amanta/Purnimanta masa, festival rules, city catalogue and search.
6. Reference parity tests, edge cases, API documentation and deployment hardening.

## Current status

The Laravel API skeleton and basic angular primitives are committed. Astronomy-dependent endpoints deliberately return HTTP 503 until a verified ephemeris adapter is available. This prevents plausible-looking but inaccurate Panchang output.

The original Python source and upstream tests are retained for reference during the port. They are not executed by the Laravel application.
