# RNIDS / RSreg EPP Client

Modern PHP 8.1+ client library for RNIDS/RSreg EPP integration.

This package provides a fluent, RNIDS-first API with deterministic XML generation, typed DTOs internally, and predictable command execution over native stream transport.

## Installation

```bash
composer require rnids/rsreg-epp-client
```

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

Run quality gates:

```bash
vendor/bin/phpcs
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
