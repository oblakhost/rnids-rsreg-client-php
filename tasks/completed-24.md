# Task 24: Refactor Contact Create/Update Normalization for Policy Enforcement

## Objective
Refactor `ContactRequestFactory` and adjacent normalization flow so ID and extension comment policies are enforced centrally without breaking existing public method signatures.

## Implementation Plan
- [x] Update create request parsing to accept optional `id`.
- [x] Route all create IDs through policy normalization (Task 22).
- [x] Route all update IDs through policy normalization (Task 22).
- [x] Apply forced extension comment assignment (Task 23) in both create/update paths.
- [x] Keep existing validation for:
  - Postal info structure
  - Disclose value (`0|1`)
  - Status list typing
  - "At least one change field" rule for update
- [x] Verify no behavior change outside intended policy updates.

## Acceptance Criteria
- Public API method signatures remain unchanged (`create(array)`, `update(array)`).
- Create accepts missing ID and generates one.
- Update still requires ID, now normalized for prefix.
- Existing valid payloads continue to work.

## Verification Commands
- `vendor/bin/phpunit tests/Unit/Contact/ContactServiceTest.php`
- `vendor/bin/phpstan analyse --debug --no-progress --memory-limit=1G src/Contact tests/Unit/Contact`
- `vendor/bin/phpcs src/Contact tests/Unit/Contact`

## Dependencies / Notes
- Requires Tasks 22 and 23.
- This task should not introduce domain/host/session changes.

## Outcome
Refactored contact create/update request assembly in `ContactRequestFactory` so policy enforcement happens in one place while preserving the existing public API surface used by `ContactService`.

Create flow now accepts missing/empty IDs via `ContactIdPolicy::normalizeForCreate()`, update flow requires and normalizes IDs via `ContactIdPolicy::normalizeForUpdate()`, and both paths apply the enforced extension comment constant for `identDescription`. Existing validation rules for postal info, disclose flags, statuses, and the update "at least one change" rule were preserved.
