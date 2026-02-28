# Task 9: Move CLI entrypoint to Composer binary rsreg

## Objective
Relocate the standalone root CLI script into `bin/rsreg`, add a proper shebang, and expose it via Composer binary metadata so consumers can run it as `vendor/bin/rsreg` after install. Keep behavior equivalent to the existing CLI commands while updating path-sensitive bootstrapping and usage text.

## Implementation Plan
- [x] Create `bin/rsreg` from existing CLI logic with shebang and corrected autoload path.
- [x] Remove legacy root `cli.php` entrypoint.
- [x] Update `composer.json` with `bin` metadata for `bin/rsreg`.
- [x] Run focused validation for the new binary entrypoint and Composer config.
- [x] Finalize task record with outcome notes, mark as completed, and commit changes.

## Outcome
Implemented a single Composer-exposed CLI binary at `bin/rsreg` with no compatibility wrapper. The previous root `cli.php` entrypoint was moved/removed, and the binary now includes a shebang plus corrected project-root path resolution for autoload and certificate candidate lookup.

### Delivered changes
- Added executable shebang to `bin/rsreg` and switched bootstrap to `__DIR__ . '/../vendor/autoload.php`.
- Updated internal file candidate resolution to use project root (`dirname(__DIR__)`) so fixture/certificate fallback paths remain correct after moving under `bin/`.
- Updated usage/examples text to reference `vendor/bin/rsreg`.
- Added Composer binary metadata: `"bin": ["bin/rsreg"]`.
- Removed legacy root `cli.php`.

### Validation
- `php -l bin/rsreg`
- `composer validate --no-check-publish --strict` (valid with existing license warning)
