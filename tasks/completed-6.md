# Task 6: Finalize domain register slice and dependent live domain tests

## Objective
Finalize the domain register command slice as part of the domain API completion effort, and reshape live integration tests to follow a deterministic lifecycle where a uniquely registered domain is reused across dependent domain operations. This aligns test behavior with real RNIDS workflows and validates that post-register operations execute immediately on the created domain.

## Implementation Plan
- [x] Introduce live integration register test that creates a unique domain and returns it for dependent tests.
- [x] Apply PHPUnit `Depends`-based ordering for domain lifecycle tests (register -> check -> info).
- [x] Extend integration configuration with register-specific fixtures (registrant/admin/tech/nameservers/auth info) and skip behavior for missing required values.
- [x] Keep stable fixture coverage using configured test domain (`komodarstvo.rs` by default) for read-only assertions.
- [x] Add or update unit coverage where needed for register-related validation/config behavior.
- [x] Run targeted PHPUnit checks plus PHPCS and PHPStan validation.

## Outcome
Implemented a dependency-driven live domain lifecycle around a newly registered unique domain and completed the register configuration support required for RNIDS test execution.

### Delivered changes
- Updated `tests/Integration/RnidsLiveIntegrationTest.php` to:
  - add `testDomainRegisterCreatesUniqueDomain()` which registers and returns a generated domain,
  - enforce lifecycle order with PHPUnit `#[Depends]` for check/info on that exact domain,
  - keep stable fixture validation path through `komodarstvo.rs` (or `RNIDS_EPP_TEST_DOMAIN`).
- Extended `tests/Integration/Support/IntegrationConfig.php` with register fixtures and helpers:
  - `ensureRegisterReadyOrSkip()` for register-specific environment requirements,
  - `uniqueRegisterDomainName()` generator,
  - `domainRegisterRequest()` shaped payload builder,
  - optional env-backed `authInfo` and nameserver list support.
- Added new unit coverage in `tests/Unit/Integration/Support/IntegrationConfigTest.php` for generated domain format and register payload mapping.

### Validation executed
- `vendor/bin/phpunit --configuration phpunit.xml.dist tests/Unit/Domain/DomainServiceTest.php tests/Unit/Xml/Domain/DomainRegisterRequestBuilderTest.php tests/Unit/Integration/Support/IntegrationConfigTest.php tests/Integration/RnidsLiveIntegrationTest.php`
- `vendor/bin/phpcs tests/Integration/RnidsLiveIntegrationTest.php tests/Integration/Support/IntegrationConfig.php tests/Unit/Integration/Support/IntegrationConfigTest.php`
- `vendor/bin/phpstan analyse tests/Integration/RnidsLiveIntegrationTest.php tests/Integration/Support/IntegrationConfig.php tests/Unit/Integration/Support/IntegrationConfigTest.php --no-progress`
