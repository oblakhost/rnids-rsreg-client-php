# Task 14: Reduce service construction noise and parser/builder re-instantiation

## Objective
Improve maintainability and readability by reducing repetitive in-method object instantiation in services and removing null-placeholder constructor wiring in `Client`, while preserving current behavior and API compatibility.

## Implementation Plan
- [x] Add default factory methods to services for request builders/parsers used by operations.
- [x] Refactor service methods to use the shared factory methods/properties instead of repeated `new` statements.
- [x] Simplify `Client` service initialization to avoid passing multiple `null` placeholder arguments.
- [x] Update/add unit tests if constructor behavior changes require it.
- [x] Run targeted PHPUnit suite for touched service/client areas.
- [x] Commit all uncommitted files.

## Outcome
Refactored `SessionService`, `DomainService`, `ContactService`, and `HostService` to cache request builders and response parsers as service properties initialized once in constructors, then reused per operation method.

Simplified `Client` service wiring by switching to named arguments and passing only `transport` + `lastResponseMetadata`, removing null placeholder constructor arguments while preserving behavior.

Validated changes by running targeted unit tests:

- `tests/Unit/Session/SessionServiceTest.php`
- `tests/Unit/Contact/ContactServiceTest.php`
- `tests/Unit/Host/HostServiceTest.php`
- `tests/Unit/Domain/DomainServiceTest.php`

All targeted tests passed (33 tests, 104 assertions).
