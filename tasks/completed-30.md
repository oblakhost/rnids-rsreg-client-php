# Task 30: Poll resData Typed Parsing

## Objective
Implement typed parsing for poll response `resData` payloads (starting with `domain:trnData`) to reach full behavioral parity with old-client poll handling while preserving the new strict DTO-first API style.

## Implementation Plan
- [x] Add typed DTO(s) for poll transfer `resData` fields (`name`, `trStatus`, `reID`, `reDate`, `acID`, `acDate`, `exDate`).
- [x] Extend session XML parser to detect and parse `domain:trnData` via namespace-safe XPath.
- [x] Extend poll response mapping so typed transfer data is exposed in service output without breaking current consumers.
- [x] Add unit tests for parser and service mapping for poll responses with and without `resData`.
- [x] Update API/session docs with new poll payload contract.

## Acceptance Criteria
- Poll responses that include `domain:trnData` are mapped into typed fields in the new client.
- Existing poll behavior for queue metadata and message text remains unchanged.
- Unit test coverage proves parsing and mapping for both plain and transfer poll messages.

## Outcome
Completed. Added typed poll transfer payload support via new `PollDomainTransferData` DTO and extended `PollResponse` to expose `domainTransferData`. `PollResponseParser` now detects/parses `domain:trnData` under `resData` with namespace-safe XPath and maps it into typed fields (`name`, `trStatus`, `reID`, `reDate`, `acID`, `acDate`, `exDate`).

Updated `SessionResponseMapper` and `SessionService::poll()` response contract to include `domainTransferData` while keeping existing queue metadata behavior unchanged. Added/updated unit tests in `PollResponseParserTest` and `SessionResponseMapperTest` to cover both queue-only and transfer payload scenarios, and updated `docs/api-session.md` with the new poll response shape.
