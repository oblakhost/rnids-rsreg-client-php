# Contributing

Thank you for contributing to `rnids/rsreg-epp-client`.

This project is a modern RNIDS/RSreg EPP client for PHP 8.1+, with strict typing, deterministic XML behavior, and fluent service APIs.

## Development setup

### Requirements

- PHP 8.1+
- Composer
- `ext-json`

### Install dependencies

```bash
composer install
```

## Project standards

- Keep RNIDS/RSreg behavior explicit and first-class.
- Prefer typed DTOs and small single-purpose methods.
- Keep XML composition/parsing deterministic and namespace-safe.
- Preserve fluent API discoverability from `RNIDS\Client` entry points.

## Quality gates

Run these checks before opening a PR:

```bash
composer test
```

Useful individual commands:

```bash
composer test:unit
composer phpstan
composer phpcs
composer test:coverage
composer test:coverage:ci
```

### Live integration suite

Run live RNIDS integration tests explicitly:

```bash
composer test:live
```

These tests depend on external connectivity and credentials/certificates. If preflight conditions are not met, suites are skipped with explicit reasons.

## Coding conventions

- Use `declare(strict_types=1);` in PHP source files.
- Use explicit parameter/return types on public methods.
- Mark classes `final` by default unless extension is intentional.
- Prefer immutable/readonly DTO-style structures where practical.
- Keep public API names aligned with RNIDS usage terminology.

## Commit message format

This repository uses semantic-release and Conventional Commits.

Format:

```text
type(scope): Description starting with a capital letter
```

Examples:

- `feat(domain): Add transfer query response mapper`
- `fix(xml): Correct namespace registration for host parser`
- `test: Expand session poll integration coverage`

Rules:

- Capitalize the first word of the description.
- Do not end the subject line with a period.
- Keep the subject concise (preferably under 72 chars).

## Pull requests

Please include:

1. A clear summary of what changed and why.
2. Any relevant protocol/behavior notes (especially RNIDS-specific behavior).
3. Tests added/updated for behavior changes.
4. Confirmation that local quality gates pass.

## Documentation updates

If you change public behavior, update relevant docs in `docs/` and cross-link from `README.md` when appropriate.
