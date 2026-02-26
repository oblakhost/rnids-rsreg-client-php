# Coding Standards

This project must remain strict, predictable, and easy to maintain.

## Baseline Tooling

- PHPCS ruleset: `phpcs.xml`
  - `PSR12`
  - `Oblak-Slevomat`
  - `PHPCompatibility` with `testVersion=8.0-`
- PHPStan: `phpstan.neon` (level 5)

## Required Code Rules

- Add `declare(strict_types=1);` to all PHP source files.
- Use explicit parameter and return types on all public methods.
- Use typed properties (avoid untyped state).
- Avoid `mixed` in public API unless there is a strong reason.
- Prefer immutable value objects / DTOs for request and response payloads.
- Mark classes `final` by default unless extension is intentional.
- Use `readonly` properties where appropriate (PHP 8.1 target platform).
- Keep methods small and single-purpose.
- Keep RNIDS-specific behavior explicit and readable.

## API Design Rules

- Prioritize fluent, discoverable API in `RNIDS\` namespace.
- Avoid generic, registry-agnostic abstractions that dilute RNIDS use-cases.
- Model RNIDS fields as first-class typed properties (not opaque extension bags).

## Quality Gate Before Commit

Run both checks before committing:

```bash
vendor/bin/phpcs
vendor/bin/phpstan analyse
```
