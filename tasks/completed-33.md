# Task 33: Local Coverage Gate and Test Quality Cleanup

## Objective
Add local, measurable coverage enforcement for the unit suite and remove low-value placeholder assertions from active tests.

## Implementation Plan
- [x] Add a dedicated local coverage command for unit tests.
- [x] Generate local coverage artifact (`build/coverage.xml`) and enforce threshold in a local script.
- [x] Remove placeholder assertion usage from unit test suite.
- [x] Add focused unit coverage around integration config behavior (host/port override and invalid port validation).
- [x] Document local coverage workflow in README.

## Acceptance Criteria
- A single local command produces coverage and enforces threshold.
- Placeholder assertion (`assertTrue(true)`) is removed.
- Coverage workflow is documented and reproducible.

## Outcome
Completed. Added `composer test:coverage` and new `bin/coverage-gate.php` to parse Clover XML and enforce a `90%` minimum line coverage threshold locally.

Updated `tests/Unit/Integration/Support/IntegrationConfigTest.php` to remove the placeholder assertion and add meaningful assertions for configured host/port usage, invalid `RNIDS_EPP_PORT` rejection, and live-readiness signal when credentials are missing.

README development section now documents `test`, `test:local`, `test:live`, and `test:coverage` with coverage-driver requirement notes.
