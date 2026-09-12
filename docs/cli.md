# Command-line client

`vendor/bin/rsreg` is the command-line interface for this independent, third-party
RSreg EPP client. The project is unaffiliated with RNIDS. Registry access requires
your own authorized credentials and client certificate.

Run `vendor/bin/rsreg --help` for the command list. Help also accepts `-h` and `help`
and works without credentials or a registry connection. In a source checkout,
run `php bin/rsreg --help` after `composer install`.

## Configuration

Set configuration through environment variables. Certificate paths are explicit;
the CLI does not search the checkout for credential files or assume a certificate
password. Unset certificate passwords support unencrypted private keys.

| Variable | Default / meaning |
| --- | --- |
| `RNIDS_EPP_USERNAME` | Required registry username |
| `RNIDS_EPP_PASSWORD` | Required registry password |
| `RNIDS_EPP_CLIENT_CERT_PATH` | Required readable PEM client certificate/private key file |
| `RNIDS_EPP_CLIENT_CERT_PASSWORD` | Unset; supply the private-key password when needed |
| `RNIDS_EPP_CA_CERT_PATH` | Unset; use system CA trust, or provide a readable CA bundle |
| `RNIDS_EPP_HOST` | `epp-test.rnids.rs`; set the endpoint provided for your environment |
| `RNIDS_EPP_PORT` | `700` |
| `RNIDS_EPP_CONNECT_TIMEOUT` | `10` seconds; positive integer |
| `RNIDS_EPP_READ_TIMEOUT` | `20` seconds; positive integer |
| `RNIDS_EPP_GREETING_MODE` | `hello` on `epp-test.rnids.rs`; `unsolicited` on other hosts |
| `RNIDS_EPP_REQUIRE_CLIENT_TRANSACTION_ID` | `false` on `epp-test.rnids.rs`; `true` on other hosts |
| `RNIDS_EPP_TLS_VERIFY_PEER` | `true` |
| `RNIDS_EPP_TLS_VERIFY_PEER_NAME` | `true` |
| `RNIDS_EPP_TLS_ALLOW_SELF_SIGNED` | `false` |
| `RNIDS_EPP_TLS_PEER_NAME` | The configured host |
| `RNIDS_EPP_TLS_DEBUG` | `false`; print TLS settings to stderr, excluding passwords |

Boolean settings accept `1,true,yes,on` and `0,false,no,off`, ignoring case and
surrounding whitespace. Invalid values fail with exit code 2. TLS remains enabled
for every endpoint; there is no plaintext CLI mode.

The test endpoint needs an explicit EPP hello before login and can omit response
`clTRID`. Its defaults account for that behavior. Commands still send `clTRID`, and
present response identifiers must match. Other hosts use the SDK's strict defaults;
override greeting mode and response-ID handling only when the endpoint requires it.

TLS verifies both the certificate chain and hostname by default. Prefer the CA
bundle supplied for your endpoint. A private development environment can explicitly
set `RNIDS_EPP_TLS_ALLOW_SELF_SIGNED=true` while retaining the verification flags.
If a controlled diagnostic requires disabling verification, opt out explicitly with
`RNIDS_EPP_TLS_VERIFY_PEER=false` and/or `RNIDS_EPP_TLS_VERIFY_PEER_NAME=false`.
These settings remove the corresponding server authentication checks.

## Commands and JSON payloads

Every invocation connects, greets the server, logs in, executes one operation, and
logs out. `session:hello` and `session:poll` use the authenticated session too.
Check commands accept comma-separated names or IDs; surrounding whitespace and
empty entries are removed. Info and delete commands accept one name or ID.

```sh
vendor/bin/rsreg session:hello
vendor/bin/rsreg session:poll
vendor/bin/rsreg domain:check example.rs,test.rs
vendor/bin/rsreg domain:info example.rs
vendor/bin/rsreg contact:check CID-1,CID-2
vendor/bin/rsreg host:check ns1.example.rs,ns2.example.rs
```

Create, register, and update commands accept a single JSON object using the SDK's
array payload shape. Quote the argument to protect it from shell expansion.
See the [domain](api-domain.md), [contact](api-contact.md), and [host](api-host.md)
references for complete fields and registry requirements.

```sh
vendor/bin/rsreg domain:register '{"name":"example.rs","registrant":"CID-REG","period":1,"contacts":[{"type":"admin","handle":"CID-ADMIN"},{"type":"tech","handle":"CID-TECH"}]}'
vendor/bin/rsreg domain:update '{"name":"example.rs","add":{"statuses":["clientHold"]}}'
vendor/bin/rsreg contact:create '{"id":"OBL-NEW","postalInfo":{"name":"John Doe","address":{"streets":["123 Main St"],"city":"Belgrade","countryCode":"RS"}},"voice":"+381.111234567","email":"john@example.rs"}'
vendor/bin/rsreg contact:update '{"id":"CID-12345","email":"new@example.rs"}'
vendor/bin/rsreg host:create '{"name":"ns7.example.rs","addresses":[{"address":"192.0.2.7","ipVersion":"v4"}]}'
vendor/bin/rsreg host:update '{"name":"ns7.example.rs","add":{"addresses":[{"address":"2001:db8::7","ipVersion":"v6"}]}}'
```

Contact update also accepts the previously advertised
`{"id":"CID-12345","change":{"email":"new@example.rs"}}` form. Its `change`
fields are translated to the SDK's top-level fields. Conflicting duplicate fields
are rejected. `change` cannot replace the contact ID or contain status add/remove
operations; use top-level `addStatuses` and `removeStatuses` for those.

Domain registration also accepts the previously advertised `years` field as an
alias for `period` in years. It must be an integer from 1 to 10 and cannot be
combined with `period` or `periodUnit`. Supply both admin and tech contacts in the
registration payload; the old abbreviated example omitted these required fields.

### Renew

The CLI preserves the JSON interface while translating it to the SDK's scalar
`renew(domain, years, expiry)` arguments. `name` is required; `years` defaults to 1
and must be an integer from 1 to 10. Optional `expiry` is the current expiration
date, not the requested new date. Omit it to perform domain info first.

```sh
vendor/bin/rsreg domain:renew '{"name":"example.rs","years":1}'
vendor/bin/rsreg domain:renew '{"name":"example.rs","years":2,"expiry":"2026-09-12"}'
```

### Transfer

The `type` field selects the EPP action in the existing JSON interface:

```sh
vendor/bin/rsreg domain:transfer '{"name":"example.rs","authInfo":"secret","type":"request"}'
```

The same actions are available explicitly:

```sh
vendor/bin/rsreg domain:transfer:request '{"name":"example.rs","authInfo":"secret"}'
vendor/bin/rsreg domain:transfer:query '{"name":"example.rs"}'
vendor/bin/rsreg domain:transfer:approve '{"name":"example.rs","authInfo":"secret"}'
vendor/bin/rsreg domain:transfer:cancel '{"name":"example.rs"}'
vendor/bin/rsreg domain:transfer:reject '{"name":"example.rs"}'
```

These map to the SDK's corresponding `transferRequest`, `transferQuery`,
`transferApprove`, `transferCancel`, and `transferReject` methods. `name` is required
and `authInfo` is optional; registry policy determines when authorization is needed.
An explicit command and a supplied `type` must agree. Unknown transfer types or
payload keys are rejected.

## Output and exit codes

Successful operations print the SDK response as pretty JSON to stdout. Dates keep
the existing PHP `DateTimeImmutable` JSON representation (`date`, `timezone_type`,
`timezone`). Empty successful update/delete results are `[]`.

| Exit code | Meaning |
| --- | --- |
| `0` | Successful operation or help |
| `1` | Registry, transport, runtime, or session cleanup failure |
| `2` | Invalid command, argument, JSON payload, SDK input, or configuration |

Errors and optional TLS diagnostics go to stderr. The CLI closes the session after
success and after errors. If logout fails after a successful operation, stdout
retains the successful response and stderr reports `Session close failed` with
exit code 1. Check that response before retrying a mutation. If both the command
and logout fail, both failures are reported.
