# Task 19: Remove XML Error Suppression and Improve Parse Diagnostics

## Objective
Eliminate `@` error suppression in XML parsing and provide deterministic, debuggable malformed-XML exception handling with useful diagnostics.

## Implementation Plan
- [x] Replace suppressed `DOMDocument::loadXML` calls with libxml internal error handling.
- [x] Capture parser diagnostics and include concise context in `MalformedResponseException` messages.
- [x] Ensure parser state is reset between parses to avoid cross-test contamination.
- [x] Add/extend unit tests for malformed XML scenarios and valid XML parsing behavior.
- [x] Run `vendor/bin/phpunit tests/Unit/Xml`, full `vendor/bin/phpstan analyse`, and targeted `vendor/bin/phpcs` checks.

## Acceptance Criteria
- No `@` suppression remains in XML parser code.
- Malformed XML exceptions include actionable diagnostic context.
- XML parser behavior remains stable for valid inputs.
- XML-related unit tests pass.

## Outcome
Implemented robust libxml-based parsing flow in `XmlParser::createXPath()`:

- removed `@` suppression from `DOMDocument::loadXML`,
- enabled internal libxml error capture,
- restored previous libxml error mode after each call,
- cleared libxml error buffers before and after parsing,
- raised `MalformedResponseException` with a stable base prefix and concise diagnostics (`message`, `line`, `column`) when available.

Added focused unit coverage in `tests/Unit/Xml/Parser/XmlParserTest.php`:

- valid XML path and namespace-safe query usage,
- malformed XML exception with diagnostics,
- parse-state isolation to ensure malformed calls do not contaminate subsequent valid parses.

Verification results:

- `vendor/bin/phpunit tests/Unit/Xml/Parser/XmlParserTest.php` ✅
- `vendor/bin/phpunit tests/Unit/Xml` ✅
- `vendor/bin/phpunit` ✅ (109 tests, 401 assertions, 1 skipped)
- `vendor/bin/phpstan analyse --debug --no-progress --memory-limit=512M` ✅
- `vendor/bin/phpcs src/Xml/Parser/XmlParser.php tests/Unit/Xml/Parser/XmlParserTest.php` ✅
