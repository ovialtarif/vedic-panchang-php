# PHP / Laravel Port Status

This repository began as a fork of [pradeepsattikar/vedic-panchang-api](https://github.com/pradeepsattikar/vedic-panchang-api).

## Porting principles

- Preserve the upstream AGPL-3.0-or-later license and attribution.
- Port calculation rules; do not proxy to the Python API or invent timings.
- Keep astronomy behind an adapter so the ephemeris implementation can be tested independently.
- Match upstream JSON fields and time semantics before declaring endpoint parity.
- Verify against upstream Drik Panchang reference fixtures and edge cases.

## Completed

1. Laravel 13 application bootstrap and API route registration.
2. PHP 8.3+ Composer constraints and Windows/Linux/macOS Swiss Ephemeris FFI dependency.
3. Swiss Ephemeris adapter for Lahiri sidereal Sun/Moon longitudes and rise/set queries.
4. Initial angular Panchang primitives and unit tests.

## In progress

- Full sunrise-to-next-sunrise daily Panchang calculation.
- Tithi, Nakshatra, Yoga and Karana time spans using boundary search.
- Graha positions, lunar masa, day timings and local-time conversion.
- City catalogue import/search, festival and muhurta rule engines.
- API response parity and regression fixtures.

## Accuracy and release policy

The API must not publish a complete Panchang response until calculations match the upstream reference fixtures. Incomplete astronomy-dependent endpoints deliberately return HTTP 503. A successful HTTP response must never contain guessed sunrise, moonrise or Panchang timings.
