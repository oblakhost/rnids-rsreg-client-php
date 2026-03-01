# Task 27b: Add Live Contact + Domain Reassignment Scenarios

## Objective
Implement live integration scenarios for contact lifecycle and domain admin/tech reassignment using the new domain update command.

## Implementation Plan
- [ ] Add new dedicated integration class for live contact scenarios.
- [ ] Add scenario 1: contact create/update/info/delete lifecycle verification.
- [ ] Add scenario 2: domain admin/tech contact swap and reset verification on stable fixture domain.
- [ ] Add scenario-level groups for selective execution (`contact-lifecycle`, `contact-domain-reassign`).
- [ ] Implement cleanup guards to avoid persistent state when scenario fails mid-flow.
- [ ] Keep skip behavior explicit when live credentials/fixtures are unavailable.

## Acceptance Criteria
- Contact lifecycle scenario validates create/update/info/delete against live RNIDS.
- Domain reassignment scenario verifies admin and tech swap/reset through `domain()->info()` state checks.
- Live tests remain skip-friendly in non-live environments.

## Verification Commands
- `vendor/bin/phpunit tests/Integration/RnidsLiveContactLifecycleIntegrationTest.php --display-skipped`
- `vendor/bin/phpunit --group contact-lifecycle --display-skipped`
- `vendor/bin/phpunit --group contact-domain-reassign --display-skipped`

## Dependencies / Notes
- Requires Task 27a complete.
- Uses stable fixture domain (`komodarstvo.rs`) and contact fixture helpers.
