# Task 20: Resolve PHPCS Exception Naming Rule Conflict

## Objective
Align exception naming strategy and static-style tooling so `phpcs` passes consistently without conflicting with established exception semantics.

## Implementation Plan
- [ ] Decide and document naming strategy: keep `*Exception` class names with ruleset tuning, or rename classes and adapt API.
- [ ] Implement chosen strategy in `src/Exception/` and relevant references.
- [ ] Update `phpcs.xml` (if ruleset tuning path is selected) to explicitly encode the project convention.
- [ ] Update tests/docs/type references impacted by naming/rules changes.
- [ ] Run `vendor/bin/phpcs`, `vendor/bin/phpstan analyse`, and `vendor/bin/phpunit`.

## Acceptance Criteria
- Current `phpcs` exception-suffix violations are eliminated.
- Exception taxonomy remains clear and coherent.
- Tooling rules reflect intended project conventions.
- All quality gates pass or any remaining findings are explicitly documented as out of scope.

## Outcome
(TBD on completion)
