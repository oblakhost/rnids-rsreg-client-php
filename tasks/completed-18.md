# Task 18: Decompose Contact, Host, and Session Services

## Objective
Apply the same decomposition pattern used for `DomainService` to `ContactService`, `HostService`, and `SessionService` to improve readability, consistency, and maintainability.

## Implementation Plan
- [x] Define shared extraction strategy and naming conventions across Contact/Host/Session modules.
- [x] Extract repeated validation and normalization logic into focused collaborators per module.
- [x] Refactor each service to orchestrate builders/parsers/collaborators with minimal inline transformation logic.
- [x] Reduce complexity hotspot in `SessionService::optionalStringList` as part of extraction.
- [x] Update/add unit tests for each affected service to ensure behavior parity.
- [x] Run `vendor/bin/phpunit tests/Unit/Contact/ContactServiceTest.php tests/Unit/Host/HostServiceTest.php tests/Unit/Session/SessionServiceTest.php`, full `vendor/bin/phpstan analyse`, and targeted `vendor/bin/phpcs` checks.

## Acceptance Criteria
- Service complexity is reduced and responsibilities are clearly separated.
- PHPCS cognitive complexity warning in `SessionService` is resolved.
- Public service APIs and output shapes remain stable.
- Contact/Host/Session unit suites pass.

## Outcome
Implemented decomposition for Contact/Host/Session services using module-local collaborators:
- Added input normalizers:
  - `src/Contact/ContactInputNormalizer.php`
  - `src/Host/HostInputNormalizer.php`
  - `src/Session/SessionInputNormalizer.php`
- Added response mappers:
  - `src/Contact/ContactResponseMapper.php`
  - `src/Host/HostResponseMapper.php`
  - `src/Session/SessionResponseMapper.php`
- Refactored orchestration services:
  - `src/Contact/ContactService.php`
  - `src/Host/HostService.php`
  - `src/Session/SessionService.php`
- Added collaborator unit suites:
  - `tests/Unit/Contact/ContactInputNormalizerTest.php`
  - `tests/Unit/Contact/ContactResponseMapperTest.php`
  - `tests/Unit/Host/HostInputNormalizerTest.php`
  - `tests/Unit/Host/HostResponseMapperTest.php`
  - `tests/Unit/Session/SessionInputNormalizerTest.php`
  - `tests/Unit/Session/SessionResponseMapperTest.php`

Verification evidence:
- `vendor/bin/phpunit tests/Unit/Contact/ContactServiceTest.php tests/Unit/Host/HostServiceTest.php tests/Unit/Session/SessionServiceTest.php` ✅
- `vendor/bin/phpcs src/Contact src/Host src/Session tests/Unit/Contact tests/Unit/Host tests/Unit/Session` ✅
- `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` failed in this environment with local TCP bind error.
- `vendor/bin/phpstan analyse --no-progress --debug --memory-limit=1G` ✅
