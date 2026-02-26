# Project Overview

This repository is a Composer library for RNIDS RSreg EPP integration.

## Purpose

Build and maintain a modern, fluent, RNIDS-specific EPP client under `src/` as a replacement for the legacy implementation in `old-client/`.

## Scope

- Registry target is **RNIDS/RSreg** (not a generic multi-registry EPP abstraction).
- RNIDS-specific fields and behaviors are first-class in the API.
- Public API should support fluent usage such as:

```php
$client->domain()->register(...);
```

## Stack

- PHP library distributed via Composer
- Namespace root: `RNIDS\`
- PHP >= 8.0 (platform configured to 8.1)
- ReactPHP components for socket/event-loop based communication
