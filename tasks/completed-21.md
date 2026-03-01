# Task 21: Replace Builder With TransportFactory and Move Config Parsing Out of Client

## Objective
Remove configuration parsing side effects from `Client` and enforce clearer separation of concerns by introducing typed config normalization and a dedicated transport factory.

## Implementation Plan
- [x] Introduce typed client config model and factory for array normalization/validation.
- [x] Introduce `TransportFactory` for transport construction from typed connection/TLS config.
- [x] Refactor `Client` to consume typed config and transport factory instead of parsing raw arrays.
- [x] Remove legacy `Builder` class.
- [x] Add unit tests for config normalization and transport factory behavior.
- [x] Run targeted and full verification (`phpunit`, `phpstan`, `phpcs`).

## Acceptance Criteria
- `Client` no longer owns low-level transport/TLS config parsing logic.
- `Builder` is removed and replaced by `TransportFactory` usage.
- Public client array API remains intact.
- New typed config and factory layers are covered by tests.
- Quality checks pass, with any out-of-scope warnings documented.

## Outcome
Implemented architecture refactor with clear SoC boundary:

- Added `src/Config/ClientConfig.php` as typed immutable client configuration.
- Added `src/Config/ClientConfigFactory.php` to normalize and validate raw client arrays.
- Added `src/Connection/TransportFactory.php` to create `Transport` instances from typed configs.
- Refactored `src/Client.php` to:
  - parse once via `ClientConfigFactory`,
  - create transport via `TransportFactory`,
  - use typed config fields during `init()` login payload construction,
  - remove duplicated config helper methods and TLS parsing internals.
- Removed `src/Builder.php`.

New tests:

- `tests/Unit/Config/ClientConfigFactoryTest.php`
- `tests/Unit/Connection/TransportFactoryTest.php`

Verification:

- `vendor/bin/phpunit tests/Unit/Config/ClientConfigFactoryTest.php tests/Unit/Connection/TransportFactoryTest.php` ✅
- `vendor/bin/phpunit tests/Unit/Client/ClientLifecycleTest.php` ✅
- `vendor/bin/phpunit` ✅ (113 tests, 422 assertions, 1 skipped)
- `vendor/bin/phpstan analyse --debug --no-progress --memory-limit=512M` ✅
- `vendor/bin/phpcs` ⚠️ only pre-existing warning in `src/Session/SessionService.php` (`optionalStringList` cognitive complexity; Task 18 scope)
