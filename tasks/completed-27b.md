# Task 27b: Add Live Contact + Domain Reassignment Scenarios

## Objective
Implement live integration scenarios for contact lifecycle and domain admin/tech reassignment using the domain update command.

## Implementation Plan
- [x] Add new dedicated integration class for live contact scenarios.
- [x] Add scenario 1: contact create/update/info/delete lifecycle verification.
- [x] Add scenario 2: domain admin/tech contact swap and reset verification on stable fixture domain.
- [x] Add scenario-level groups for selective execution (`contact-lifecycle`, `contact-domain-reassign`).
- [x] Implement cleanup guards to avoid persistent state when scenario fails mid-flow.
- [x] Keep skip behavior explicit when live credentials/fixtures are unavailable.

## Acceptance Criteria
- Contact lifecycle scenario validates create/update/info/delete against live RNIDS.
- Domain reassignment scenario verifies admin and tech swap/reset through `domain()->info()` state checks.
- Live tests remain skip-friendly in non-live environments.

## Verification Commands
- `vendor/bin/phpunit tests/Integration/RnidsLiveContactLifecycleIntegrationTest.php --display-skipped`
- `vendor/bin/phpunit --group contact-lifecycle --display-skipped`
- `vendor/bin/phpunit --group contact-domain-reassign --display-skipped`

## Dependencies / Notes
- Depends on Task 27a.
- Stable target contact for reassignment is resolved via env `RNIDS_EPP_TEST_CONTACT_ID` with fallback `OBL-test-kontakt`.

## Outcome
Added a dedicated live integration class for contact lifecycle and domain contact reassignment, with cleanup protections and explicit skip behavior.

Key updates:
- Added integration class:
  - `tests/Integration/RnidsLiveContactLifecycleIntegrationTest.php`
- Added integration config helper:
  - `tests/Integration/Support/IntegrationConfig.php` (`testContactHandle()`)
- Added config unit coverage:
  - `tests/Unit/Integration/Support/IntegrationConfigTest.php`

Verification evidence:
- `vendor/bin/phpunit tests/Integration/RnidsLiveContactLifecycleIntegrationTest.php --display-skipped` ✅ (suite skipped in this environment due to DNS/connectivity)
