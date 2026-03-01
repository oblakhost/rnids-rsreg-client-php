# Task 18: Decompose Contact, Host, and Session Services

## Objective
Apply the same decomposition pattern used for `DomainService` to `ContactService`, `HostService`, and `SessionService` to improve readability, consistency, and maintainability.

## Implementation Plan
- [ ] Define shared extraction strategy and naming conventions across Contact/Host/Session modules.
- [ ] Extract repeated validation and normalization logic into focused collaborators per module.
- [ ] Refactor each service to orchestrate builders/parsers/collaborators with minimal inline transformation logic.
- [ ] Reduce complexity hotspot in `SessionService::optionalStringList` as part of extraction.
- [ ] Update/add unit tests for each affected service to ensure behavior parity.
- [ ] Run `vendor/bin/phpunit tests/Unit/Contact/ContactServiceTest.php tests/Unit/Host/HostServiceTest.php tests/Unit/Session/SessionServiceTest.php`, full `vendor/bin/phpstan analyse`, and targeted `vendor/bin/phpcs` checks.

## Acceptance Criteria
- Service complexity is reduced and responsibilities are clearly separated.
- PHPCS cognitive complexity warning in `SessionService` is resolved.
- Public service APIs and output shapes remain stable.
- Contact/Host/Session unit suites pass.

## Outcome
(TBD on completion)
