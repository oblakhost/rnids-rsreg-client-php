# Task 32: Documentation Final Polish and Release Readiness Notes

## Objective
Perform final documentation hardening for release-readiness by tightening README onboarding and ensuring API docs reflect the latest fluent contracts, policy behavior, and poll semantics.

## Implementation Plan
- [x] Add/refresh README sections for installation, requirements, and quickstart configuration.
- [x] Ensure API docs (`api-reference`, service pages) are aligned with current flattened payload contracts.
- [x] Document poll response behavior clearly, including typed `resData` support once Task 30 lands.
- [x] Add a concise release-readiness summary note in docs/tasks outcome.
- [x] Run a docs consistency pass (links, examples, command names) to eliminate drift.

## Acceptance Criteria
- README provides complete first-run guidance for new consumers.
- API docs match real service signatures and payload shapes.
- Poll docs reflect implemented behavior and examples.

## Outcome
Completed. Performed final documentation hardening across README and service API docs for release-readiness.

Updated `docs/api-domain.md` to match actual `DomainService` signatures and payload contracts, including full
`register()` signature (with `authInfo` and `extension`), transfer request shape, and update extension shape.
Updated `docs/api-session.md` poll documentation to clearly describe `operation` modes and typed
`domainTransferData` mapping when poll `resData` includes `domain:trnData`, with an example payload.

Refreshed `README.md` onboarding with an explicit Requirements section and first-run config guidance
(minimum required keys and common optional config/TLS keys) to reduce startup ambiguity for new consumers.

Docs consistency pass was completed (no TODO placeholders found, examples/signatures checked against source
services, and docs references remained valid). Quality gates executed successfully (`vendor/bin/phpcs`,
`vendor/bin/phpstan analyse`).

Release-readiness note: documentation now reflects current fluent contracts and poll semantics and is ready
for release handoff.
