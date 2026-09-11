<div align="center">

<h1 align="center" style="border-bottom: none; margin-bottom: 0px">RNIDS / RSreg EPP Client</h1>
<h3 align="center" style="margin-top: 0px">Modern Dependency-Safe PHP Client for RNIDS Registry EPP</h3>

[![Packagist Version](https://img.shields.io/packagist/v/rnids/rsreg-epp-client?label=Release&style=flat-square)](https://packagist.org/packages/rnids/rsreg-epp-client)
![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/rnids/rsreg-epp-client/php?label=PHP&logo=php&logoColor=white&logoSize=auto&style=flat-square)
![Static Badge](https://img.shields.io/badge/RNIDS-RSreg-3858e9?style=flat-square)
[![codecov](https://codecov.io/github/oblakhost/rnids-rsreg-client-php/graph/badge.svg?token=0UMFP9CL35)](https://codecov.io/github/oblakhost/rnids-rsreg-client-php)
[![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/oblakhost/rnids-rsreg-client-php/release.yml?label=Build&event=push&style=flat-square&logo=githubactions&logoColor=white&logoSize=auto)](https://github.com/oblakhost/rnids-rsreg-client-php/actions/workflows/release.yml)

</div>

This library provides a fluent, RNIDS-first implementation of the EPP protocol for PHP 8.1+ applications.
It focuses on deterministic XML handling, typed request/response modeling, strict transport behavior, and predictable command execution for RNIDS/RSreg environments.

## Key Features

1. RNIDS-first API design with fluent entry points for session, domain, contact, and host operations.
2. Deterministic EPP request lifecycle over native stream transport and frame codec boundaries.
3. Validated request arrays, typed internal DTOs, and documented response shapes.
4. Explicit protocol/transport exception strategy under `RNIDS\Exception\*`.
5. Separate XML composition/parsing modules for easier testing and maintenance.
6. Coverage-aware quality gate with static analysis and coding standards checks.

## Installation

Install via Composer:

```bash
composer require rnids/rsreg-epp-client
```

Internationalized domain and host names are converted to ASCII Punycode when building
EPP commands. Unicode input requires the PHP `intl` extension; callers without it
can supply ASCII Punycode names. Registry responses retain the ASCII names sent by
the registry. Other Unicode fields, such as contact names and remarks, are unchanged.

## Usage

```php
<?php

declare(strict_types=1);

use RNIDS\Client;

$client = Client::ready([
    'host' => 'epp.example.rs',
    'port' => 700,
    'username' => 'client-id',
    'password' => 'secret',
    'language' => 'en',
    'tls' => [
        'clientCertificatePath' => '/secure/path/client.pem',
        'clientCertificatePassword' => 'certificate-passphrase',
        'caFilePath' => '/secure/path/rnids-ca.pem',
    ],
]);

$domainInfo = $client->domain()->info('example.rs');
$meta = $client->responseMeta();

$client->close();
```

The default `greetingMode` is `unsolicited`, which reads the server greeting before
login. The RNIDS development endpoint waits for an explicit hello; set
`'greetingMode' => 'hello'` when connecting to `epp-test.rnids.rs`. Both modes wait
for the actual login result before making the client available. The development
server also omits `clTRID` on some successful responses: set
`'requireClientTransactionId' => false` there. This accepts omitted IDs only;
present IDs must still match the command. The default remains `true`.

Common fluent entry points:

- Session: `$client->session()->hello()`, `login()`, `logout()`, `poll()`
- Domain: `$client->domain()->check()`, `info()`, `register()`, `renew()`, `update()`, `delete()`, and `transferRequest()` / `transferQuery()` / `transferApprove()` / `transferCancel()` / `transferReject()`
- Contact: `$client->contact()->check()`, `create()`, `info()`, `update()`, `delete()`
- Host: `$client->host()->check()`, `info()`, `create()`, `update()`, `delete()`

Runtime contact policy:

- Contact creation retains the `OBL-...` generation policy; update, info, and delete preserve the supplied registry identifier.
- Contact `extension.identDescription` preserves caller-provided values.

Native connections require TLS by default. `allowPlaintext => true` explicitly enables
unencrypted transport for a local test peer. Tests and custom adapters can inject a
`Transport` through `new Client($config, $transport)` or `Client::ready($config, $transport)`.

Domain updates support nameservers and glue addresses. DNSSEC uses DS records in
`dnssec`, as shown in the [domain API](docs/api-domain.md); the legacy RNIDS `dnsSec`
boolean alone does not provision DNSSEC. Existing `getCode()`, `getState()`, and
`transfer()` methods remain available as compatibility aliases.

`composer test` runs the offline quality gate, including a local EPP server.
`composer test:live` is an explicit RNIDS validation run and fails if its prerequisites
are missing or any tests are skipped. See [Contributing](CONTRIBUTING.md) for setup.

## Documentation

- API Reference Index: [`docs/api-reference.md`](docs/api-reference.md)
- Client API: [`docs/api-client.md`](docs/api-client.md)
- Session API: [`docs/api-session.md`](docs/api-session.md)
- Domain API: [`docs/api-domain.md`](docs/api-domain.md)
- Contact API: [`docs/api-contact.md`](docs/api-contact.md)
- Host API: [`docs/api-host.md`](docs/api-host.md)
- EPP Protocol Reference: [`docs/epp-protocol/epp-reference-index.md`](docs/epp-protocol/epp-reference-index.md)

## Contributing

For local setup, quality gates, commit conventions, and PR guidelines, see [`CONTRIBUTING.md`](CONTRIBUTING.md).

## License

Apache-2.0. See [`LICENSE`](LICENSE).

Live cleanup records resource names and states in `RNIDS_EPP_RESOURCE_LEDGER`, or
`/tmp/rnids-live-resources-<pid>.json` by default. RNIDS may accept a domain deletion
with code 1000 while retaining `pendingDelete`; the suite verifies that state and
records the domain and its linked contacts as `pending`, not removed. Unexpected
cleanup failures still fail the run. Process pending records after registry deletion
completes. The ledger contains object identifiers and states, never credentials.
