# Task 17: Decompose DomainService Into Focused Collaborators

## Objective
Reduce `DomainService` complexity by extracting validation, normalization, and response mapping responsibilities into dedicated collaborators while preserving public behavior.

## Implementation Plan
- [x] Identify extraction boundaries in `DomainService` (input validators, normalization helpers, DTO->array mappers).
- [x] Introduce focused classes under `src/Domain/` (or subfolders) for extracted responsibilities.
- [x] Refactor `DomainService` methods to orchestrate collaborators instead of holding large private helper blocks.
- [x] Keep public method signatures and return shapes backward-compatible.
- [x] Expand unit tests to assert parity for check/info/register/renew/delete/transfer paths.
- [x] Run `vendor/bin/phpunit tests/Unit/Domain/DomainServiceTest.php`, full `vendor/bin/phpstan analyse`, and targeted `vendor/bin/phpcs` checks.

## Acceptance Criteria
- `DomainService` file size and cognitive complexity are materially reduced.
- Extracted collaborators are individually testable.
- Public API behavior and response shapes remain unchanged.
- Domain unit tests pass with parity coverage.

## Outcome
Implemented focused collaborators and reduced `DomainService` to orchestration:
- Added `src/Domain/DomainInputNormalizer.php` for request normalization/validation.
- Added `src/Domain/DomainNameserverNormalizer.php` for simplified nameserver normalization.
- Added `src/Domain/DomainResponseMapper.php` for DTO-to-array mapping.
- Refactored `src/Domain/DomainService.php` to delegate helper-heavy logic to collaborators.
- Added new unit suites:
  - `tests/Unit/Domain/DomainInputNormalizerTest.php`
  - `tests/Unit/Domain/DomainNameserverNormalizerTest.php`
  - `tests/Unit/Domain/DomainResponseMapperTest.php`

Verification:
- `vendor/bin/phpunit tests/Unit/Domain/DomainServiceTest.php tests/Unit/Domain/DomainInputNormalizerTest.php tests/Unit/Domain/DomainNameserverNormalizerTest.php tests/Unit/Domain/DomainResponseMapperTest.php` ✅
- `vendor/bin/phpcs src/Domain tests/Unit/Domain` ✅
- `vendor/bin/phpstan analyse --no-progress --debug --memory-limit=1G` ✅
