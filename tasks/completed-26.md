# Task 26: Extend Contact XML Builder Tests for Policy Output

## Objective
Ensure XML request builders for contact create/update reliably emit policy-enforced values in deterministic payloads.

## Implementation Plan
- [x] Update `ContactCreateRequestBuilder` tests to assert prefixed IDs in resulting XML.
- [x] Update `ContactUpdateRequestBuilder` tests to assert prefixed IDs in resulting XML.
- [x] Add assertions for required extension comment element value:
  - `contactExt:identDescription` exact text.
- [x] Keep existing deterministic fragment assertions for authInfo/disclose/extension blocks.
- [x] Add edge-case assertion where extension contains only enforced comment and still serializes correctly.

## Acceptance Criteria
- XML-level tests verify policy behavior at payload boundary.
- Assertions prove exact outbound shape expected by RNIDS.
- No brittle assertions on generated random suffix beyond prefix contract.

## Verification Commands
- `vendor/bin/phpunit tests/Unit/Xml/Contact`
- `vendor/bin/phpcs tests/Unit/Xml/Contact src/Xml/Contact`

## Dependencies / Notes
- Depends on Tasks 22–25.
- Keep test failures descriptive for easier debugging.

## Outcome
Extended XML request builder tests so payload boundary assertions explicitly cover the contact policy contract.

Key updates:
- `tests/Unit/Xml/Contact/ContactCreateRequestBuilderTest.php` now asserts:
  - prefixed create ID in XML,
  - exact enforced `contactExt:identDescription` value,
  - extension serialization when only policy comment is present.
- `tests/Unit/Xml/Contact/ContactUpdateRequestBuilderTest.php` now asserts:
  - prefixed update ID in XML,
  - exact enforced `contactExt:identDescription` value,
  - extension serialization when only policy comment is present.

Verification evidence:
- `vendor/bin/phpunit tests/Unit/Xml/Contact` ✅ (12 tests, 55 assertions)
- `vendor/bin/phpcs tests/Unit/Xml/Contact src/Xml/Contact` ✅
