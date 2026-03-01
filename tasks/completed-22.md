# Task 22: Introduce Contact ID Policy (`OBL-` Prefix + Auto Generation)

## Objective
Define and enforce a single contact ID policy in the library so contact objects created or updated through the client are consistently traceable to Oblak-managed flows.

## Implementation Plan
- [x] Add a dedicated contact ID policy utility under `src/Contact/` (for example `ContactIdPolicy`).
- [x] Define canonical prefix constant `OBL-` in one place.
- [x] Implement create-path normalization:
  - If `id` is missing or empty, generate `OBL-` + `uniqid()`.
  - If `id` exists and does not start with `OBL-`, prepend `OBL-`.
  - If `id` already starts with `OBL-`, keep as-is.
- [x] Implement update-path normalization:
  - `id` remains required.
  - Normalize non-prefixed IDs by prepending `OBL-`.
- [x] Wire policy into contact request normalization/factory layer.
- [x] Ensure error messages are explicit for invalid or missing update IDs.

## Acceptance Criteria
- Every outgoing `contact:create` request ID is prefixed with `OBL-`.
- Every outgoing `contact:update` request ID is prefixed with `OBL-`.
- `contact:update` without ID fails with a deterministic validation error.
- `contact:create` with no ID still succeeds by generating a valid prefixed ID.

## Verification Commands
- `vendor/bin/phpunit tests/Unit/Contact`
- `vendor/bin/phpcs src/Contact tests/Unit/Contact`

## Dependencies / Notes
- This task is foundational for Tasks 24, 25, 26, and 27.
- Keep implementation library-only. Do not change WHMCS module code.

## Outcome
Implemented `src/Contact/ContactIdPolicy.php` with a centralized `OBL-` prefix policy and create/update normalization methods. The policy was wired into `ContactRequestFactory` so create requests can auto-generate prefixed IDs when missing/empty, while update requests require a non-empty ID and normalize it to the enforced prefix.

Added dedicated unit coverage in `tests/Unit/Contact/ContactIdPolicyTest.php` and policy-focused factory coverage in `tests/Unit/Contact/ContactRequestFactoryPolicyTest.php` to verify generation, prefix preservation, prefix injection, and required-update-ID validation behavior.
