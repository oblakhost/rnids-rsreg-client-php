# Task 17: Decompose DomainService Into Focused Collaborators

## Objective
Reduce `DomainService` complexity by extracting validation, normalization, and response mapping responsibilities into dedicated collaborators while preserving public behavior.

## Implementation Plan
- [ ] Identify extraction boundaries in `DomainService` (input validators, normalization helpers, DTO->array mappers).
- [ ] Introduce focused classes under `src/Domain/` (or subfolders) for extracted responsibilities.
- [ ] Refactor `DomainService` methods to orchestrate collaborators instead of holding large private helper blocks.
- [ ] Keep public method signatures and return shapes backward-compatible.
- [ ] Expand unit tests to assert parity for check/info/register/renew/delete/transfer paths.
- [ ] Run `vendor/bin/phpunit tests/Unit/Domain/DomainServiceTest.php`, full `vendor/bin/phpstan analyse`, and targeted `vendor/bin/phpcs` checks.

## Acceptance Criteria
- `DomainService` file size and cognitive complexity are materially reduced.
- Extracted collaborators are individually testable.
- Public API behavior and response shapes remain unchanged.
- Domain unit tests pass with parity coverage.

## Outcome
(TBD on completion)
