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
- Minimum PHP version: 8.1 - leveraging modern features like union types, attributes, and readonly properties
- ReactPHP components for socket/event-loop based communication
