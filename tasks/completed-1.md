# Task 1: Native streams transport baseline and test foundation

## Objective
Replace ReactPHP socket/event-loop dependencies with a native PHP stream transport direction for the RNIDS EPP client, establish PHPUnit as the testing baseline with project configuration, and document architecture/testing decisions in `.clinerules` so the implementation path is explicit and maintainable.

## Implementation Plan
- [x] Remove React dependencies from `composer.json` and add PHPUnit-related dev tooling/scripts.
- [x] Add PHPUnit project configuration (`phpunit.xml.dist`) and test bootstrap/scaffolding under `tests/`.
- [x] Revise `.clinerules` architecture/testing guidance to define native stream transport as default and React as out of scope.
- [x] Update `src/` architecture scaffolding to reflect a transport-first native stream design.
- [x] Run Composer/test/static-quality commands and resolve any issues.

## Acceptance Criteria
- `composer.json` no longer includes React packages.
- PHPUnit dependencies and runnable test configuration are present.
- `.clinerules` clearly documents the transport decision and testing expectations.
- `src/` includes clear typed architecture scaffolding for native transport components.
- Quality checks relevant to this change run successfully (or documented if blocked).

## Outcome
Implemented the native-stream-first baseline and removed all React dependencies from the project. `composer.json` now includes PHPUnit and PHPStan PHPUnit integration in `require-dev`, adds test/lint scripts, and uses `autoload-dev` for `Tests\`. `composer update` was run successfully and `composer.lock` was updated accordingly.

Added transport scaffolding in `src/Connection/` (`Transport`, `ConnectionConfig`, `TlsConfig`, `EppFrameCodec`, `NativeStreamTransport`) and updated `Client`, `Builder`, and `SocketClient` to use this typed transport flow. Updated `.clinerules/03-architecture.md` and `.clinerules/06-testing.md` to codify native stream transport as mandatory core behavior and to require PHPUnit config/bootstrap plus transport-focused test coverage.

Added PHPUnit baseline files: `phpunit.xml.dist`, `tests/bootstrap.php`, unit test for frame codec, and an integration placeholder test. Quality gates now pass:
- `vendor/bin/phpunit --configuration phpunit.xml.dist`
- `vendor/bin/phpcs`
- `vendor/bin/phpstan analyse`
