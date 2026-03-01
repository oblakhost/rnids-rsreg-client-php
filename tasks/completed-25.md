# Task 25: Add Table-Driven Unit Tests for Contact Policy Rules

## Objective
Build robust unit coverage for new contact ID and extension comment policies using table-driven scaffolding compatible with existing test conventions.

## Implementation Plan
- [x] Add a new focused unit suite for contact ID policy utility.
- [x] Add data-provider/table-driven tests covering create ID normalization:
  - Missing ID
  - Empty/whitespace ID
  - Non-prefixed ID
  - Already-prefixed ID
- [x] Add data-provider/table-driven tests covering update ID normalization:
  - Non-prefixed ID normalization
  - Prefixed ID pass-through
  - Missing ID rejection
- [x] Add tests proving forced `identDescription` override behavior.
- [x] Ensure tests assert deterministic error messages for invalid inputs.

## Acceptance Criteria
- Policy behaviors are covered by explicit, isolated tests.
- No policy branch remains untested.
- Tests are readable and reusable via shared fixture/data-provider scaffolding.

## Verification Commands
- `vendor/bin/phpunit tests/Unit/Contact`
- `vendor/bin/phpcs tests/Unit/Contact src/Contact`

## Dependencies / Notes
- Requires Tasks 22–24.
- Keep tests independent from network/live integration.

## Outcome
Expanded contact policy unit coverage into data-provider-driven tests while preserving existing behavior.

Key updates:
- Refactored `tests/Unit/Contact/ContactIdPolicyTest.php` to table-driven coverage for create/update normalization paths.
- Refactored `tests/Unit/Contact/ContactRequestFactoryPolicyTest.php` to table-driven coverage for:
  - create ID normalization matrix,
  - update ID normalization matrix,
  - deterministic invalid update ID errors,
  - forced `identDescription` override on both create and update.
- Added deterministic message assertion for invalid create ID type in policy utility.

Verification evidence:
- `vendor/bin/phpunit tests/Unit/Contact` ✅ (37 tests, 70 assertions)
- `vendor/bin/phpcs tests/Unit/Contact src/Contact` ✅
- `vendor/bin/phpstan analyse --debug --no-progress --memory-limit=1G src/Contact tests/Unit/Contact` ✅
