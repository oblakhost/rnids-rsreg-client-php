<div align="center">

<h1 align="center" style="border-bottom: none; margin-bottom: 0px">RNIDS / RSreg EPP Client</h1>
<h3 align="center" style="margin-top: 0px">Modern Dependency-Safe PHP Client for RNIDS Registry EPP</h3>

[![Packagist Version](https://img.shields.io/packagist/v/rnids/rsreg-epp-client?label=Release&style=flat-square)](https://packagist.org/packages/rnids/rsreg-epp-client)
![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/rnids/rsreg-epp-client/php?label=PHP&logo=php&logoColor=white&logoSize=auto&style=flat-square)
![Static Badge](https://img.shields.io/badge/RNIDS-RSreg-3858e9?style=flat-square)
[![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/oblakhost/rnids-rsreg-client-php/release.yml?label=Build&event=push&style=flat-square&logo=githubactions&logoColor=white&logoSize=auto)](https://github.com/oblakhost/rnids-rsreg-client-php/actions/workflows/release.yml)

</div>

This library provides a fluent, RNIDS-first implementation of the EPP protocol for PHP 8.1+ applications.
It focuses on deterministic XML handling, typed request/response modeling, strict transport behavior, and predictable command execution for RNIDS/RSreg environments.

## Key Features

1. RNIDS-first API design with fluent entry points for session, domain, contact, and host operations.
2. Deterministic EPP request lifecycle over native stream transport and frame codec boundaries.
3. Typed service-layer DTOs and normalized response mapping for reliable integrations.
4. Explicit protocol/transport exception strategy under `RNIDS\Exception\*`.
5. Separate XML composition/parsing modules for easier testing and maintenance.
6. Coverage-aware quality gate with static analysis and coding standards checks.

## Installation

Install via Composer:

```bash
composer require rnids/rsreg-epp-client
```

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
]);

$domainInfo = $client->domain()->info('example.rs');
$meta = $client->responseMeta();

$client->close();
```

Common fluent entry points:

- Session: `$client->session()->hello()`, `login()`, `logout()`, `poll()`
- Domain: `$client->domain()->check()`, `info()`, `register()`, `renew()`, `update()`, `delete()`, `transfer()`
- Contact: `$client->contact()->check()`, `create()`, `info()`, `update()`, `delete()`
- Host: `$client->host()->check()`, `info()`, `create()`, `update()`, `delete()`

Runtime contact policy:

- Contact IDs are normalized to `OBL-...` for create/update flows.
- Contact `extension.identDescription` is enforced to `Object Creation provided by Oblak Solutions.`

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
