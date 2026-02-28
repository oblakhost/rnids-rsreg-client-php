# Task 12: Standardize public API list responses and docs

## Objective
Remove single-key wrapper arrays (for example `['items' => [...]]`) from public API responses to keep response payloads consistent and flatter across services. Update affected tests, PHPDoc shaped-array annotations, and public API docs so implementation and documentation stay aligned.

## Implementation Plan
- [x] Identify all public API methods that return single-key wrapper arrays and confirm affected call sites.
- [x] Refactor affected public service methods to return direct lists instead of `items` wrappers.
- [x] Update unit/integration tests to assert the new standardized response shape.
- [x] Audit and improve PHPDoc for public API methods (especially shaped-array return annotations).
- [x] Update docs in `docs/` to reflect exact public request/response shapes for affected methods.
- [x] Run targeted validation checks and summarize results.
- [ ] Finalize task outcome, rename to completed state, and commit all changes.

## Outcome
Implemented API shape standardization for list-based check operations by removing single-key `items` wrappers from:

- `RNIDS\Domain\DomainService::check()`
- `RNIDS\Host\HostService::check()`

Updated all affected tests to assert direct list responses:

- `tests/Unit/Domain/DomainServiceTest.php`
- `tests/Unit/Host/HostServiceTest.php`
- `tests/Integration/RnidsLiveIntegrationTest.php`

Updated PHPDoc for affected public methods, including shaped-array annotation improvement for `DomainService::register()` extension argument.

Updated public API docs with explicit request/response shapes and noted direct-list check responses:

- `docs/api-domain.md`
- `docs/api-host.md`
- `docs/api-session.md`
- `docs/api-reference.md`

Validation run summary:

- `vendor/bin/phpunit tests/Unit/Domain/DomainServiceTest.php tests/Unit/Host/HostServiceTest.php tests/Integration/RnidsLiveIntegrationTest.php` ✅ (24 tests, 76 assertions, 1 skipped integration)
- `vendor/bin/phpstan analyse` ✅ (no errors)
- `vendor/bin/phpcs` ⚠️ baseline pre-existing issues in exception naming and complexity warnings unrelated to this task
