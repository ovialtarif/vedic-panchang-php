# PHP / Laravel Port Status

This repository began as a fork of [pradeepsattikar/vedic-panchang-api](https://github.com/pradeepsattikar/vedic-panchang-api).

## Porting principles

- Preserve the upstream AGPL-3.0-or-later license and attribution.
- Port calculation rules; do not proxy to the Python API or invent timings.
- Keep astronomy behind an adapter so the ephemeris implementation can be tested independently.
- Match upstream JSON fields and time semantics before declaring endpoint parity.
- Verify against upstream Drik Panchang reference fixtures and edge cases.

## Implemented

1. Laravel 13 application bootstrap and API route registration.
2. PHP 8.3+ Composer constraints and Swiss Ephemeris FFI dependency.
3. Swiss Ephemeris adapter for Lahiri sidereal Sun/Moon longitudes and rise/set queries.
4. Angular Panchang primitives and cyclic span search with bisection boundary refinement.
5. Canonical names and index calculations for Tithi, Nakshatra, Yoga, Karana and Rashi.
6. Navagraha positions (Sun, Moon, Mars, Mercury, Jupiter, Venus, Saturn, Rahu and Ketu), retrograde flags and Lahiri sidereal ascendant calculation.
7. Julian Day conversion and timezone-aware local civil-day boundaries.
8. Amanta and Purnimanta lunar month naming, including Adhika month detection.
9. Sunrise/sunset-derived Rahu Kalam, Yamaganda, Gulika Kalam, Abhijit Muhurta and Brahma Muhurta intervals.
10. Coordinate-based daily Panchang orchestration and serialization.

## In progress

- Full response parity and regression fixtures against upstream.
- City catalogue search, nearest-city lookup and city-ID integration.
- Month and festival endpoints, festival rules and puja/muhurta calculations.
- Shubh-yoga parity and validation of year/ritu/ayana rules.
- Cross-platform Swiss Ephemeris runtime verification.

## Accuracy and release policy

The coordinate-based daily endpoint currently invokes the PHP calculation engine, but it is not yet declared upstream-compatible or production-ready. Calculation exceptions return a generic HTTP 422 response. Incomplete city, month and festival endpoints deliberately return HTTP 503. A successful HTTP response must never contain guessed sunrise, moonrise or Panchang timings.
