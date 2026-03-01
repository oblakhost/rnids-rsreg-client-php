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

$client = new Client([
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

## Fluent API

- Session: `$client->session()->hello()`, `login()`, `logout()`, `poll()`
- Domain: `$client->domain()->check()`, `info()`, `register()`, `renew()`, `delete()`, `transfer()`
- Contact: `$client->contact()->check()`, `create()`, `info()`, `update()`, `delete()`
- Host: `$client->host()->check()`, `info()`, `create()`, `update()`, `delete()`

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

## Development

Run quality gates:

```bash
vendor/bin/phpcs
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
