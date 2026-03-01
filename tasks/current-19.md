# Task 19: Remove XML Error Suppression and Improve Parse Diagnostics

## Objective
Eliminate `@` error suppression in XML parsing and provide deterministic, debuggable malformed-XML exception handling with useful diagnostics.

## Implementation Plan
- [ ] Replace suppressed `DOMDocument::loadXML` calls with libxml internal error handling.
- [ ] Capture parser diagnostics and include concise context in `MalformedResponseException` messages.
- [ ] Ensure parser state is reset between parses to avoid cross-test contamination.
- [ ] Add/extend unit tests for malformed XML scenarios and valid XML parsing behavior.
- [ ] Run `vendor/bin/phpunit tests/Unit/Xml`, full `vendor/bin/phpstan analyse`, and targeted `vendor/bin/phpcs` checks.

## Acceptance Criteria
- No `@` suppression remains in XML parser code.
- Malformed XML exceptions include actionable diagnostic context.
- XML parser behavior remains stable for valid inputs.
- XML-related unit tests pass.

## Outcome
(TBD on completion)
