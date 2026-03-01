# Task 13: Implement contact command slice with deterministic fixtures

## Objective
Implement the RNIDS contact slice in the new client with fluent operations (`check`, `create`, `info`, `update`, `delete`), deterministic XML build/parse coverage, and a reusable seeded deterministic fixture strategy for integration tests (individual/company profiles), while keeping public API/docs aligned.

## Implementation Plan
- [x] Add Contact DTOs, request factory, and service API for check/create/info/update/delete.
- [x] Add `src/Xml/Contact/*` request builders and response parsers for all contact commands.
- [x] Wire contact service into `Client` fluent API and CLI/docs references where needed.
- [x] Add deterministic contact fixture factory and unit/integration tests for contact lifecycle scenarios.
- [x] Add targeted tests for domain admin/tech change vs separate registrant-change path.
- [x] Run validation (`phpunit`, `phpstan`, `phpcs`) and fix issues introduced by this task.
- [ ] Finalize outcome, rename task file to completed state, and commit.

## Outcome
Implemented a full RNIDS contact slice with fluent operations (`check`, `create`, `info`, `update`, `delete`) including typed request normalization and XML builders/parsers under `src/Contact` and `src/Xml/Contact`. Added focused unit coverage for service behavior and XML determinism, plus deterministic seeded fixtures (`tests/Support/ContactFixtureFactory.php`) with supporting tests and integration config wiring (`tests/Integration/Support/IntegrationConfig.php`).

Public API docs were updated to include the new contact service in `README.md`, `docs/api-reference.md`, and `docs/api-client.md`, with dedicated contact documentation in `docs/api-contact.md`. Quality checks for the touched scope were executed successfully (`phpcs`, `phpstan`, and contact-focused unit PHPUnit run).
