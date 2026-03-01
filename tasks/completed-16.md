# Task 16: Harden Shutdown and Error Visibility in Client Close/Destruct Paths

## Objective
Replace silent exception swallowing in `Client::close()` / destructor flow with an explicit shutdown-error policy that keeps shutdown resilient while retaining diagnostic visibility.

## Implementation Plan
- [x] Define shutdown error policy (e.g., capture and expose last shutdown exception, optional logger callback, no throw from destructor).
- [x] Refactor `Client::close()` to follow policy and avoid empty `catch` blocks.
- [x] Ensure destructor delegates safely without hiding operational failures in normal explicit close flows.
- [x] Add tests for logout failures, disconnect failures, idempotent close calls, and destructor-safe behavior.
- [x] Update docs/API notes to clarify shutdown guarantees and error reporting behavior.
- [x] Run `vendor/bin/phpunit`, `vendor/bin/phpstan analyse`, and targeted `vendor/bin/phpcs` checks for touched files.

## Acceptance Criteria
- No silent empty `catch` remains in client shutdown logic.
- Explicit `close()` surfaces or records failures per agreed policy.
- Destructor remains exception-safe while preserving diagnostics.
- Tests cover shutdown edge cases.

## Outcome
Implemented explicit shutdown policy in `Client`:

- `close()` now throws when logout/disconnect fails.
- `__destruct()` remains non-throwing and suppresses shutdown exceptions.
- Added `lastCloseError(): ?\Throwable` to expose the last captured shutdown failure.

Refactored shutdown internals into small private methods (`logoutAndCaptureError`, `disconnectAndCaptureError`, `finalizeClose`) to keep complexity manageable and make failure handling deterministic.

Expanded `tests/Unit/Client/ClientLifecycleTest.php` with Task 16 coverage:

- explicit close throws and records logout failure,
- explicit close throws and records disconnect failure,
- destructor suppresses exceptions while recording diagnostics.

Documentation updates:

- `docs/api-client.md` now documents `close()` throwing behavior and `lastCloseError()`.
- `README.md` now includes shutdown behavior notes.

Verification results:

- `vendor/bin/phpunit` ✅ (106 tests, 397 assertions, 1 skipped)
- `vendor/bin/phpstan analyse --debug --no-progress --memory-limit=512M` ✅
- `vendor/bin/phpcs src/Client.php tests/Unit/Client/ClientLifecycleTest.php docs/api-client.md README.md` ✅
