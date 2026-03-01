# Task 27a: Add Domain Update Command Support

## Objective
Implement first-class domain update support in the library so callers can modify domain contacts and related update sections using typed API methods.

## Implementation Plan
- [x] Add domain update DTOs for request/section/response payload shape.
- [x] Add XML builder/parser for `domain:update` command.
- [x] Extend `DomainService` with `update()` command wiring and metadata handling.
- [x] Add/extend input normalization and validation for domain update payloads.
- [x] Add unit tests for XML builder/parser and service behavior.
- [x] Ensure update flow returns empty mapped response on success.

## Acceptance Criteria
- `Client::domain()->update(array $request)` is available and sends valid EPP `domain:update` XML.
- API supports contact reassignment operations required for admin/tech swap scenarios.
- Invalid/missing update mutation payloads fail with explicit validation errors.
- New unit tests cover request building and command execution path.

## Verification Commands
- `vendor/bin/phpunit tests/Unit/Xml/Domain/DomainUpdateRequestBuilderTest.php`
- `vendor/bin/phpunit tests/Unit/Xml/Domain/DomainUpdateResponseParserTest.php`
- `vendor/bin/phpunit tests/Unit/Domain/DomainServiceUpdateTest.php`
- `vendor/bin/phpunit tests/Unit/Domain tests/Unit/Xml/Domain`

## Dependencies / Notes
- Enables Task 27b contact-domain reassignment live scenario.
- Followed existing Domain/Host command structure and naming conventions.

## Outcome
Added complete domain update command support with typed DTOs, deterministic XML builder/parser, and service integration.

Key updates:
- Added DTOs:
  - `src/Domain/Dto/DomainUpdateRequest.php`
  - `src/Domain/Dto/DomainUpdateSection.php`
  - `src/Domain/Dto/DomainUpdateResponse.php`
- Added XML components:
  - `src/Xml/Domain/DomainUpdateRequestBuilder.php`
  - `src/Xml/Domain/DomainUpdateResponseParser.php`
- Extended service:
  - `src/Domain/DomainService.php` with `update(array $request): array`.
- Added unit coverage:
  - `tests/Unit/Xml/Domain/DomainUpdateRequestBuilderTest.php`
  - `tests/Unit/Xml/Domain/DomainUpdateResponseParserTest.php`
  - `tests/Unit/Domain/DomainServiceUpdateTest.php`

Verification evidence:
- `vendor/bin/phpunit tests/Unit/Xml/Domain/DomainUpdateRequestBuilderTest.php tests/Unit/Xml/Domain/DomainUpdateResponseParserTest.php tests/Unit/Domain/DomainServiceUpdateTest.php tests/Unit/Domain tests/Unit/Xml/Domain` ✅ (`47` tests, `194` assertions)
