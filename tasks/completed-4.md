# Task 4: Implement session hello and poll flows

## Objective
Add full session support for EPP `hello` and `poll` operations in the new RNIDS client, including typed DTOs, deterministic XML builders, namespace-safe response parsers, and `SessionService` API methods with unit test coverage.

## Implementation Plan
- [x] Add hello DTOs, request builder, response parser, and service method.
- [x] Add poll DTOs, request builder, response parser, and service method.
- [x] Add unit tests for hello/poll XML build and parse behavior plus service integration behavior.
- [x] Run targeted PHPUnit and PHPStan checks for changed scope.
- [x] Finalize task outcome notes and mark task as completed.

## Outcome
Implemented full session `hello` and `poll` command support in the new client stack.
Added new typed DTOs for hello/poll responses and poll requests, plus deterministic XML builders
for hello/poll commands and namespace-safe parsers for greeting and queue data extraction.

Extended `SessionService` with `hello()` and `poll()` public APIs, including input validation for
poll operation (`req`/`ack`) and required `messageId` for ack flows. Added shaped-array PHPDocs
for newly exposed response payloads.

Added unit coverage for hello/poll request builders, response parsers, and end-to-end session
service behavior using transport stubs. Ran targeted checks successfully:
- `vendor/bin/phpunit --configuration phpunit.xml.dist tests/Unit/Xml/Session tests/Unit/Session`
- `vendor/bin/phpstan analyse src/Session src/Xml/Session tests/Unit/Session tests/Unit/Xml/Session --no-progress`
