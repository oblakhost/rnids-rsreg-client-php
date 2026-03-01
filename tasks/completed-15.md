# Task 15: Decouple Client Construction From Network Session Bootstrap

## Objective
Remove network side effects from `Client::__construct` so object creation is deterministic and safe, while preserving fluent API ergonomics and providing an explicit lifecycle step for connect/hello/login.

## Implementation Plan
- [x] Define target lifecycle API (`connectAndLogin()` or equivalent) and document constructor guarantees.
- [x] Refactor `Client::__construct` to only validate/configure dependencies without opening transport or sending EPP commands.
- [x] Move current bootstrap flow (`connect` + `hello` + `login`) into explicit lifecycle method(s).
- [x] Update service access methods and transport guards so behavior remains consistent after delayed bootstrap.
- [x] Update unit/integration tests to cover both uninitialized and initialized client states.
- [x] Update docs examples to use explicit bootstrap lifecycle.
- [x] Run `vendor/bin/phpunit`, `vendor/bin/phpstan analyse`, and targeted `vendor/bin/phpcs` checks for touched files.

## Acceptance Criteria
- Constructing `Client` performs no network I/O.
- Session bootstrap is explicit and testable.
- Existing fluent usage remains supported after explicit bootstrap.
- Tests verify lifecycle behavior and failure modes.

## Outcome
Implemented explicit client lifecycle bootstrap with `Client::init()` and added convenience `Client::ready(array $config)` for one-line ready-to-use flows.

`Client::__construct` no longer performs connection, hello, or login side effects. Service access methods (`session()`, `domain()`, `contact()`, `host()`) now fail fast before initialization with an explicit runtime message.

Added lifecycle coverage in `tests/Unit/Client/ClientLifecycleTest.php`, including constructor side-effect checks, pre-init guard behavior, init idempotency, ready factory behavior, and close idempotency. Updated live integration bootstrap to use `Client::ready(...)`.

Updated usage docs in `README.md` and `docs/api-client.md` to document explicit initialization and convenience factory usage.

Verification results:
- `vendor/bin/phpunit` ✅ (103 tests, 385 assertions, 1 skipped integration suite)
- `vendor/bin/phpstan analyse --debug --no-progress --memory-limit=512M` ✅
- `vendor/bin/phpcs src/Client.php` ✅
- `vendor/bin/phpcs` reports only pre-existing repository issues in exception naming and one SessionService complexity warning.
