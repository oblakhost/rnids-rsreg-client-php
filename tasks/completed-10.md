# Task 10: Simplify Public Endpoint Parameters

## Objective
Analyze and optimize public fluent endpoint parameters to reduce array-heavy single-key payloads and introduce clearer scalar-first APIs for common RNIDS operations, while preserving backward compatibility with existing array-based calls.

## Implementation Plan
- [x] Review and update `DomainService` public method signatures for scalar-first ergonomics where appropriate.
- [x] Review and update `HostService` public method signatures for scalar-first ergonomics where appropriate.
- [x] Add internal normalization paths so legacy array payloads still function.
- [x] Implement `domain()->renew($domain, $years)` public flow with internal `curExpDate` resolution.
- [x] Enforce/normalize yearly renew semantics in the simplified renew API.
- [x] Keep domain register extension as array input and document allowed keys.
- [x] Update unit tests to cover both new scalar usage and legacy array compatibility.
- [x] Update docs/examples to prefer simplified fluent method forms.
- [x] Run quality checks and tests.

## Acceptance Criteria
- Public API supports scalar-first usage for targeted endpoints (`domain()->check`, `host()->check`, `host()->create`, and simplified domain renew flow).
- Domain renew simplified API requires only domain + years while still sending protocol-required `curExpDate` internally.
- Existing array-based callers remain supported (no immediate breaking change).
- Tests cover new behavior and compatibility paths.
- Documentation reflects preferred usage patterns.

## Outcome
Implemented scalar-first endpoint improvements while preserving legacy array payload compatibility.

### Implemented API improvements
- `DomainService::check()` now accepts `string|array`.
- `HostService::check()` now accepts `string|array`.
- `HostService::create()` now supports simplified signature:
  - `create(string $hostname, ?string $ipv4 = null, ?string $ipv6 = null)`
- `DomainService::renew()` now supports simplified signature:
  - `renew(string $domain, int $years)` with internal `domain:info` lookup for protocol-required `curExpDate`.
- `DomainService::register()` now supports a simplified scalar-first form while keeping existing array input.
  - Simplified path enforces nameservers and normalizes hostname shorthand into existing request format.

### Tests and docs
- Added/updated unit tests for new scalar paths and compatibility in:
  - `tests/Unit/Domain/DomainServiceTest.php`
  - `tests/Unit/Host/HostServiceTest.php`
- Updated docs with convenience API guidance:
  - `docs/epp-domain-commands.md`
  - `docs/epp-host-commands.md`

### Validation
- `vendor/bin/phpunit tests/Unit/Domain/DomainServiceTest.php tests/Unit/Host/HostServiceTest.php` ✅
- `vendor/bin/phpstan analyse` ✅
- `vendor/bin/phpcs` still reports pre-existing repository-level violations outside this task scope (Exception naming/cognitive complexity in untouched files).
