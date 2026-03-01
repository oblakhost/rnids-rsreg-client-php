# Task 30: Poll resData Typed Parsing

## Objective
Implement typed parsing for poll response `resData` payloads (starting with `domain:trnData`) to reach full behavioral parity with old-client poll handling while preserving the new strict DTO-first API style.

## Implementation Plan
- [ ] Add typed DTO(s) for poll transfer `resData` fields (`name`, `trStatus`, `reID`, `reDate`, `acID`, `acDate`, `exDate`).
- [ ] Extend session XML parser to detect and parse `domain:trnData` via namespace-safe XPath.
- [ ] Extend poll response mapping so typed transfer data is exposed in service output without breaking current consumers.
- [ ] Add unit tests for parser and service mapping for poll responses with and without `resData`.
- [ ] Update API/session docs with new poll payload contract.

## Acceptance Criteria
- Poll responses that include `domain:trnData` are mapped into typed fields in the new client.
- Existing poll behavior for queue metadata and message text remains unchanged.
- Unit test coverage proves parsing and mapping for both plain and transfer poll messages.

## Outcome
(Filled in on completion — what was done, any deviations from the plan, follow-up notes.)
