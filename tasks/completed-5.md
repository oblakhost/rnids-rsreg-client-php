# Task 5: Implement domain info command slice

## Objective
Add the next high-value parity slice by implementing domain info (`domain:info`) in the new RNIDS client, including typed request/response DTOs, deterministic XML request generation, namespace-safe XML response parsing, RNIDS domain extension field extraction, and domain service API/test coverage.

## Implementation Plan
- [x] Add domain info request/response/value DTOs.
- [x] Add `DomainInfoRequestBuilder` for deterministic `domain:info` XML generation.
- [x] Add `DomainInfoResponseParser` for core domain and RNIDS extension fields.
- [x] Extend `DomainService` with `info()` request validation, execution, and shaped-array mapping.
- [x] Add unit tests for builder/parser/service behavior and validation errors.
- [x] Run targeted quality checks (PHPUnit, PHPStan, PHPCS).

## Outcome
Implemented end-to-end domain info support in the new client.

Added new DTOs under `src/Domain/Dto/` (`DomainInfoRequest`, `DomainInfoResponse`, `DomainInfoStatus`, `DomainInfoContact`, `DomainInfoNameserver`, `DomainInfoExtension`), new XML components (`src/Xml/Domain/DomainInfoRequestBuilder.php`, `src/Xml/Domain/DomainInfoResponseParser.php`), and extended `DomainService::info()` with strict validation (`name` and `hosts` handling) and shaped-array response mapping.

Added tests:
- `tests/Unit/Xml/Domain/DomainInfoRequestBuilderTest.php`
- `tests/Unit/Xml/Domain/DomainInfoResponseParserTest.php`
- Updated `tests/Unit/Domain/DomainServiceTest.php`

Validated successfully with:
- `vendor/bin/phpunit --configuration phpunit.xml.dist tests/Unit/Xml/Domain tests/Unit/Domain`
- `vendor/bin/phpstan analyse src/Domain src/Xml/Domain tests/Unit/Domain tests/Unit/Xml/Domain --no-progress`
- `vendor/bin/phpcs src/Domain src/Xml/Domain tests/Unit/Domain tests/Unit/Xml/Domain`
