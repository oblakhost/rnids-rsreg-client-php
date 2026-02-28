# Host Service API

`RNIDS\Host\HostService` exposes nameserver host commands.

## Methods

### `check(string|array $request): array`

Checks one or many host objects.

### `info(string $name): array`

Returns host details, statuses, IP addresses, and timestamps.

### `create(string|array $request, ?string $ipv4 = null, ?string $ipv6 = null): array`

Creates a host.

Supports full payload or simplified form:

```php
$host->create('ns1.example.rs', '192.0.2.10', null);
```

### `update(array $request): array{}`

Updates addresses/statuses and/or renames host.

### `delete(string $name): array{}`

Deletes a host object.
