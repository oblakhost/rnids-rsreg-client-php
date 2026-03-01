# Task 35: Deterministic Coverage Commands and Meaningful Unit Coverage Expansion

## Objective
Make local coverage execution deterministic with PCOV, add a Codecov/CI-oriented coverage command, and raise unit line coverage to satisfy the strict local threshold through meaningful tests.

## Implementation Plan
- [x] Force-enable PCOV in local coverage scripts.
- [x] Add a dedicated CI/Codecov coverage command that outputs Clover XML.
- [x] Expand unit tests for low-coverage pure logic paths without placeholder assertions.
- [x] Document command behavior and coverage artifact path.

## Acceptance Criteria
- `composer test:coverage` deterministically enables PCOV and enforces threshold.
- `composer test:coverage:ci` produces `build/coverage.xml` suitable for Codecov uploads.
- Added tests validate behavior and error paths (no coverage-only fluff).
- Coverage gate passes at the configured threshold.

## Outcome
Completed. Updated `composer.json` so `test:coverage` and `test:coverage:ci` both run PHPUnit via
`php -d pcov.enabled=1` and enforce the same `90%` line-coverage gate from `build/coverage.xml`.

Added focused unit suites and branch assertions for:
- `DomainRegisterRequestFactory`
- `HostRequestFactory`
- `DomainInputNormalizer`
- `SessionInputNormalizer`

Updated README coverage docs to distinguish local human-readable coverage from CI/Codecov artifact generation.
