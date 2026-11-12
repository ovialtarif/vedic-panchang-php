# PHP / Laravel Port Status

This repository began as a fork of [pradeepsattikar/vedic-panchang-api](https://github.com/pradeepsattikar/vedic-panchang-api).

## Porting principles

- Preserve the upstream AGPL-3.0-or-later license and attribution.
- Port calculation rules; do not proxy to the Python API or invent timings.
- Keep astronomy behind an adapter so the ephemeris implementation can be tested independently.
- Match upstream JSON fields and time semantics before declaring endpoint parity.
- Verify against upstream Drik Panchang reference fixtures and edge cases.

## Completed

8. Daily Panchang orchestration for coordinate-based requests: sunrise/sunset, moonrise/moonset, sunrise-to-next-sunrise fallback, element spans, Vara, Masa, year info, grahas and day timings.

1. Laravel 13 application bootstrap and API route registration.
2. PHP 8.3+ Composer constraints and Swiss Ephemeris FFI dependency.
3. Swiss Ephemeris adapter for Lahiri sidereal Sun/Moon longitudes and rise/set queries.
4. Initial angular Panchang primitives.
5. Upstream-compatible cyclic angular span search with bisection boundary refinement.
6. Canonical names and index calculations for Tithi, Nakshatra, Yoga, Karana and Rashi.
7. Unit tests for the mathematical primitives and cyclic span boundary finder.
8. Navagraha positions (Sun, Moon, Mars, Mercury, Jupiter, Venus, Saturn, Rahu and Ketu), retrograde flags and Lahiri sidereal ascendant calculation.
9. Julian Day conversion and timezone-aware local civil-day boundaries, including daylight-saving transitions.
10. Amanta and Purnimanta lunar month naming, including Adhika month detection from consecutive new moons and solar sign transitions.
11. Sunrise/sunset-derived Rahu Kalam, Yamaganda, Gulika Kalam, Abhijit Muhurta and Brahma Muhurta intervals.

## In progress

- Full sunrise-to-next-sunrise daily Panchang calculation and serialization using the new date and astronomy primitives.
- Local civil-date boundaries and rise/set event handling for all locations.
- Graha positions, lunar masa, day timings and local-time conversion.
- City catalogue import/search, festival and muhurta rule engines.
- API response parity and regression fixtures.

## Accuracy and release policy

The API must not publish a complete Panchang response until calculations match the upstream reference fixtures. Incomplete astronomy-dependent endpoints deliberately return HTTP 503. A successful HTTP response must never contain guessed sunrise, moonrise or Panchang timings.
