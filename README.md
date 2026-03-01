# RNIDS / RSreg EPP Client

Modern PHP 8.1+ client library for RNIDS/RSreg EPP integration.

This package provides a fluent, RNIDS-first API with deterministic XML generation, typed DTOs internally, and predictable command execution over native stream transport.

## Installation

```bash
composer require rnids/rsreg-epp-client
```

## Requirements

- PHP 8.1+
- `ext-json`
- Network access to RNIDS/RSreg EPP endpoint
- TLS certificates configured in client config when required by your environment

## Quick Start

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
]);

$domainInfo = $client->domain()->info('example.rs');

// Optional metadata from the latest EPP response:
$meta = $client->responseMeta();

$client->close();
```

Minimum config keys for first run:

- `host`
- `username`
- `password`

Common optional keys:

- `port` (default `700`)
- `language` (default `en`)
- `version` (default `1.0`)
- `objectUris`, `extensionUris`
- `tls` options (`localCertPath`, `localPkPath`, `passphrase`, `caFile`, `verifyPeer`, `verifyPeerName`, `allowSelfSigned`, `peerName`)

If you need explicit lifecycle control:

```php
$client = new Client([...]);
$client->init();
```

## Fluent API

- Session: `$client->session()->hello()`, `login()`, `logout()`, `poll()`
- Domain: `$client->domain()->check()`, `info()`, `register()`, `renew()`, `update()`, `delete()`, `transfer()`
- Contact: `$client->contact()->check()`, `create()`, `info()`, `update()`, `delete()`
- Host: `$client->host()->check()`, `info()`, `create()`, `update()`, `delete()`

Contact runtime policy:

- Contact IDs are normalized to `OBL-...` for create/update requests.
- Contact `extension.identDescription` is enforced to:
  `Object Creation provided by Oblak Solutions.`

## Documentation

- API Reference Index: [`docs/api-reference.md`](docs/api-reference.md)
- Client API: [`docs/api-client.md`](docs/api-client.md)
- Session API: [`docs/api-session.md`](docs/api-session.md)
- Domain API: [`docs/api-domain.md`](docs/api-domain.md)
- Contact API: [`docs/api-contact.md`](docs/api-contact.md)
- Host API: [`docs/api-host.md`](docs/api-host.md)
- EPP Protocol Reference: [`docs/epp-protocol/epp-reference-index.md`](docs/epp-protocol/epp-reference-index.md)

## Error Handling

Protocol and transport issues are surfaced as exceptions from `RNIDS\Exception\*`.

Successful operations return typed/normalized data arrays from service methods, while low-level response context is available via:

```php
<?php

$client->responseMeta();
```

Shutdown behavior:

- Explicit `$client->close()` may throw on logout/disconnect failure.
- Automatic destructor shutdown is non-throwing.
- Inspect `$client->lastCloseError()` for the last shutdown failure, if any.

## Development

Default local test gate (offline-safe):

```bash
composer test
```

Expanded local quality gate (equivalent behavior):

```bash
composer test:local
```

Live RNIDS integration suites only:

```bash
composer test:live
```

Live suites run preflight checks for required environment variables, certificate files,
DNS, and TCP endpoint reachability. If prerequisites are missing, suites are skipped with
an explicit reason instead of failing the default local workflow.

Local coverage gate (unit tests + threshold):

```bash
composer test:coverage
```

`test:coverage` enables `pcov` explicitly (`php -d pcov.enabled=1`) and enforces the
line-coverage threshold using `build/coverage.xml`.

Codecov/CI coverage artifact command:

```bash
composer test:coverage:ci
```

`test:coverage:ci` writes Clover XML to `build/coverage.xml` (ready for upload) and
applies the same strict threshold gate.
