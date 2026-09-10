# Contributing

Thank you for contributing to `rnids/rsreg-epp-client`.

This project is a modern RNIDS/RSreg EPP client for PHP 8.1+, with strict typing, deterministic XML behavior, and fluent service APIs.

## Development setup

### Requirements

- PHP 8.1+
- Composer
- `ext-json`, `ext-dom`, and `ext-openssl`

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

`composer test:live` fails when any live test is skipped. A direct full PHPUnit run may skip unavailable integration tests, so passing the unit gate does not certify RNIDS interoperability.

Configure the RNIDS **test registry** before running mutations:

- `RNIDS_EPP_USERNAME`, `RNIDS_EPP_PASSWORD`: an authorized test registrar account.
- `RNIDS_EPP_CLIENT_CERT_PATH`: PEM containing the client certificate and matching private key; `RNIDS_EPP_CLIENT_CERT_PASSWORD` unlocks an encrypted key (set it to an empty string for an unencrypted key).
- `RNIDS_EPP_CA_CERT_PATH`: trusted test-registry CA PEM. Peer and hostname verification are enabled.
- `RNIDS_EPP_HOST` and `RNIDS_EPP_PORT`: optional endpoint overrides; defaults are `epp-test.rnids.rs:700`.
- `RNIDS_EPP_REGISTER_NAMESERVERS`: comma-separated nameservers permitted by the test registry.
- `RNIDS_EPP_TEST_HOST_IPV4`: an allowed IPv4 address for the temporary host lifecycle.
- `RNIDS_EPP_TEST_DOMAIN`: an existing authorized fixture used only for a read-only info check.
- `RNIDS_EPP_POLL_ACK_MESSAGE_ID`: exact ID of an approved disposable test message currently at the head of this account's queue. The acknowledgement test refuses a different message; it never drains the queue. This variable must be refreshed after a successful run.

Preflight checks local credentials, PEM parsing, key matching, and certificate validity before trying TCP connectivity. The dummy unit-test certificate is excluded from discovery. Configure explicit certificate paths for reliable runs; never commit real credentials or keys.

The mutation scenarios create their own contacts, domain, and host, exercise updates and domain renewal, then delete resources in dependency order. Cleanup failures fail the test and identify resources requiring manual removal. Domain creation/renewal requires sufficient test-account credit. Registry policy, approval delays, interrupted connections, or deletion restrictions can still require manual cleanup.

CI runs live tests only through the `run_live` manual workflow input, or on pushes with repository variable `RNIDS_RUN_LIVE=true`. When enabled, missing secrets or skipped tests fail the job. Configure secrets `RNIDS_EPP_USER`, `RNIDS_EPP_PASS`, `RNIDS_EPP_CERT`, `RNIDS_EPP_ROOT`, and `RNIDS_EPP_CLIENT_CERT_PASSWORD`, plus repository variables matching the fixture names above. Otherwise the live job is visibly skipped.

Transfer lifecycle verification remains an external prerequisite: it needs two authorized registrar accounts, certificates for each, a disposable domain, and the RNIDS transfer-code/approval workflow. This suite does not claim successful transfer coverage from a single account or fabricated responses. Secure-mode approval flows and real server interoperability also require a scheduled RNIDS test run after access is available.

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
