# Task 20: Resolve PHPCS Exception Naming Rule Conflict

## Objective
Align exception naming strategy and static-style tooling so `phpcs` passes consistently without conflicting with established exception semantics.

## Implementation Plan
- [x] Decide and document naming strategy: keep `*Exception` class names with ruleset tuning, or rename classes and adapt API.
- [x] Implement chosen strategy in `src/Exception/` and relevant references.
- [x] Update `phpcs.xml` (if ruleset tuning path is selected) to explicitly encode the project convention.
- [x] Update tests/docs/type references impacted by naming/rules changes.
- [x] Run `vendor/bin/phpcs`, `vendor/bin/phpstan analyse`, and `vendor/bin/phpunit`.

## Acceptance Criteria
- Current `phpcs` exception-suffix violations are eliminated.
- Exception taxonomy remains clear and coherent.
- Tooling rules reflect intended project conventions.
- All quality gates pass or any remaining findings are explicitly documented as out of scope.

## Outcome
Implemented ruleset-tuning strategy to preserve conventional public exception names while removing PHPCS naming conflict.

Changes:

- Updated `phpcs.xml` to keep `Oblak-Slevomat` while explicitly excluding only:
  - `SlevomatCodingStandard.Classes.SuperfluousExceptionNaming.SuperfluousSuffix`

This resolves exception suffix findings for:

- `EppException`
- `ProtocolException`
- `TransportException`
- `MalformedResponseException`

without renaming API classes or touching exception hierarchy.

Verification results:

- `vendor/bin/phpcs -s src/Exception/EppException.php src/Exception/ProtocolException.php src/Exception/TransportException.php src/Exception/MalformedResponseException.php` ✅
- `vendor/bin/phpcs` ⚠️ only remaining pre-existing warning in `src/Session/SessionService.php` (`optionalStringList` cognitive complexity, tracked by Task 18)
- `vendor/bin/phpstan analyse --debug --no-progress --memory-limit=512M` ✅
- `vendor/bin/phpunit` ✅ (109 tests, 401 assertions, 1 skipped)
