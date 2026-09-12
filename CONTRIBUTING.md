# Contributing

Thank you for contributing to `rnids/rsreg-epp-client`.

This project is an independent third-party RNIDS/RSreg EPP client for PHP 8.1+,
with strict typing, deterministic XML behavior, and fluent service APIs. It is not
affiliated with, endorsed by, or supported by RNIDS. Follow the
[2.x compatibility policy](SUPPORT.md) when changing public behavior.

## Development setup

### Requirements

- PHP 8.1+
- Composer
- `ext-json`, `ext-dom`, and `ext-openssl`
- `ext-intl` for Unicode domain/host input tests
- A coverage driver such as PCOV for coverage runs
- Bash, Git, `unzip`, and `sha256sum` for distribution checks; Composer archive
  creation also needs either PHP's `zip` extension or the `zip` executable

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
composer build:dist
composer test:dist
```

PHPStan runs with a 512 MB memory limit so the same command works on PHP setups
whose default limit is 128 MB. Socket tests and PHPStan workers need permission
to bind local loopback sockets; they do not need registry connectivity.

Distribution commands validate committed source: `build:dist` packages `HEAD`,
and `test:dist` checks extracted Git and Composer archives, including autoloading
and the bundled CLI. Commit source changes before the final distribution check.
Run the ordinary local gate before committing. CI checks the committed artifacts
and all required offline gates before publishing that exact revision.

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

The integration configuration uses the observed development-server behavior:
`greetingMode=hello` and `requireClientTransactionId=false`. Supplied transaction
IDs must still match. Endpoint overrides do not automatically change these test
settings; do not point this mutation suite at production.

The mutation scenarios create their own contacts, domain, and host, exercise updates and domain renewal, then delete resources in dependency order. Cleanup failures fail the test and identify resources requiring manual removal. Domain creation/renewal requires sufficient test-account credit. Registry policy, approval delays, interrupted connections, or deletion restrictions can still require manual cleanup.

Set `RNIDS_EPP_RESOURCE_LEDGER` to retain an ownership ledger at a known location;
otherwise it is written to `/tmp/rnids-live-resources-<pid>.json`. Accepted domain
deletion may leave `pendingDelete` and linked contacts. These resources are recorded
as pending, not removed. Revisit only recorded owned resources after deletion
completes; contact identifiers may remain unavailable even after the contact object
is gone. The suite verifies contact absence through an info response with `2303`.

CI runs live tests only through the `run_live` manual workflow input, after the
offline quality gate passes. Pushes run offline checks and may publish a release;
they do not perform registry mutations. The former `RNIDS_RUN_LIVE` repository
variable no longer starts live runs. When requested, missing secrets or skipped
tests fail the live job. Configure secrets `RNIDS_EPP_USER`, `RNIDS_EPP_PASS`,
`RNIDS_EPP_CERT`, `RNIDS_EPP_ROOT`, and `RNIDS_EPP_CLIENT_CERT_PASSWORD`, plus
repository variables matching the fixture names above. Live runs share one
repository-wide concurrency group and an active run is not canceled by a newer run.

Transfer lifecycle verification remains an external prerequisite: it needs two authorized registrar accounts, certificates for each, a disposable domain, and the RNIDS transfer-code/approval workflow. This suite does not claim successful transfer coverage from a single account or fabricated responses.

The September 11, 2026 run at `421256a` completed five existing live cases with 52
assertions and skipped poll acknowledgment because no message was approved. The
command exited 1 due to `--fail-on-skipped`. DNSSEC DS acceptance, complete transfer
workflows, secure-mode changes, and contact clearing require additional recorded
scenarios; they are not established by those five cases. See the LTS acceptance
conditions in [SUPPORT.md](SUPPORT.md).

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
