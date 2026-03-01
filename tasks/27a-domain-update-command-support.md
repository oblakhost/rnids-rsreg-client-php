# Task 27a: Add Domain Update Command Support

## Objective
Implement first-class domain update support in the library so callers can modify domain contacts and related update sections using typed API methods.

## Implementation Plan
- [ ] Add domain update DTOs for request/section/response payload shape.
- [ ] Add XML builder/parser for `domain:update` command.
- [ ] Extend `DomainService` with `update()` command wiring and metadata handling.
- [ ] Add/extend input normalization and validation for domain update payloads.
- [ ] Add unit tests for XML builder/parser and service behavior.
- [ ] Ensure update flow returns empty mapped response on success.

## Acceptance Criteria
- `Client::domain()->update(array $request)` is available and sends valid EPP `domain:update` XML.
- API supports contact reassignment operations required for admin/tech swap scenarios.
- Invalid/missing update mutation payloads fail with explicit validation errors.
- New unit tests cover request building and command execution path.

## Verification Commands
- `vendor/bin/phpunit tests/Unit/Xml/Domain/DomainUpdateRequestBuilderTest.php`
- `vendor/bin/phpunit tests/Unit/Xml/Domain/DomainUpdateResponseParserTest.php`
- `vendor/bin/phpunit tests/Unit/Domain/DomainServiceTest.php --filter update`

## Dependencies / Notes
- Enables Task 27b contact-domain reassignment live scenario.
- Follow existing Domain/Host command structure and naming conventions.
