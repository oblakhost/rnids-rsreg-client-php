# Task 11: Revise documentation structure and API docs

## Objective
Reorganize project documentation so EPP protocol references live under a dedicated `docs/epp-protocol/` folder, add a structured client API documentation set inspired by KnpLabs/php-github-api style, add/normalize PHPDocs for public API methods, and create a root `README.md` that gives clear onboarding and navigation across docs.

## Implementation Plan
- [x] Create protocol documentation folder structure and move existing EPP docs.
- [x] Update protocol doc links/indexes so navigation remains correct after move.
- [x] Add client API docs in `docs/` with consistent per-method format and examples.
- [x] Add missing PHPDoc blocks for public API methods with short descriptions and detailed notes where needed.
- [x] Create `README.md` with installation, quick-start, usage, and docs map.
- [x] Run quality checks, capture outcome, and finalize task state.

## Outcome
Completed documentation restructuring and API docs pass for the public client surface:

- Moved EPP protocol docs into `docs/epp-protocol/`.
- Added client API documentation pages: `docs/api-reference.md`, `docs/api-client.md`, `docs/api-session.md`, `docs/api-domain.md`, `docs/api-host.md`.
- Added root `README.md` with installation, quick start, API overview, and docs map.
- Added/normalized short PHPDoc descriptions for public methods that were missing in the host DTO/parser/builder area and metadata holder.

Validation:

- `vendor/bin/phpstan analyse` passed.
- `vendor/bin/phpcs` reports existing baseline issues in exception naming and complexity warnings unrelated to this task’s documentation scope.
