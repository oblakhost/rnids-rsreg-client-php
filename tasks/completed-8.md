# Task 8: Global response metadata and flattened service payloads

## Objective
Refactor service response contracts to remove repeated `metadata` blocks and nested single-key wrappers, while exposing response metadata globally through `Client::responseMeta()`. This keeps the fluent RNIDS API clean and domain-focused while preserving access to protocol-level status information.

## Implementation Plan
- [x] Add shared response metadata storage and expose `Client::responseMeta()`.
- [x] Update command execution flow to persist last parsed response metadata globally.
- [x] Remove per-method `metadata` payloads and flatten single-key wrappers in session/domain/host services.
- [x] Update tests to assert the new response shape and global metadata behavior.
- [x] Run test/quality commands and confirm green results.
- [x] Update task outcome, optionally refresh soul memory note, and finalize commit.

## Acceptance Criteria
- [x] `Client::responseMeta()` returns the latest parsed response metadata as array shape `{resultCode, message, clientTransactionId, serverTransactionId}` or `null` before first response.
- [x] Service methods no longer include `metadata` in returned payloads.
- [x] Single-key wrappers (`info`, `creation`, `transfer`, `queue`) are flattened where applicable.
- [x] Existing tests are updated (or new tests added) to validate new contracts.
- [x] Changes are committed with a Conventional Commit message.

## Outcome
Implemented global response metadata tracking through a shared `LastResponseMetadata` holder and exposed it via `Client::responseMeta()`. Updated `CommandExecutor` to persist metadata on every parsed response, wired all services to share this holder through the client, and removed duplicated response-level metadata from service payloads.

Flattened service return payloads where previous single-key wrappers were unnecessary (`info`, `creation`, `renewal`, `transfer`, `queue`) and aligned integration/unit tests with the new contract. Added a dedicated unit test for command-executor metadata persistence and updated soul memory with the completed architectural milestone.

Validation executed: `vendor/bin/phpunit --testsuite unit` (pass), `vendor/bin/phpstan analyse` (pass). `vendor/bin/phpcs` still reports pre-existing baseline issues in exception class naming/cognitive complexity unrelated to this task.
