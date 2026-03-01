# Task 34: Offline-First Test Command Strategy and Live Preflight Skips

## Objective
Make default test execution deterministic for local development while preserving live RNIDS integration verification as an explicit command.

## Implementation Plan
- [x] Make default `composer test` use offline-safe checks.
- [x] Add dedicated `composer test:live` for integration suites.
- [x] Add live integration preflight checks for env/certificates/DNS/TCP reachability.
- [x] Skip live suites with explicit reason when prerequisites are not ready.
- [x] Document command behavior in README.

## Acceptance Criteria
- `composer test` does not require RNIDS connectivity.
- Live suites are invoked explicitly through `composer test:live`.
- Missing live prerequisites result in clear skip reasons.

## Outcome
Completed. `composer test` now maps to the offline-safe local quality gate (`test:local`) and a dedicated `composer test:live` script now runs integration suites only.

Added `IntegrationConfig::liveReadinessFailureReason()` and wired both live test classes to call `markTestSkipped(...)` in `setUpBeforeClass()` when readiness checks fail. Preflight now validates required credentials, cert readability, host resolution, endpoint TCP reachability, and `RNIDS_EPP_PORT` validity.

README now clearly separates offline default testing from explicit live integration verification.
