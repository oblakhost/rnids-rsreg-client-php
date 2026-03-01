# Host Service API

`RNIDS\Host\HostService` exposes nameserver host commands.

## Methods

### `check(string|array $request): array`

Checks one or many host objects.

Request shape:

```php
array{names?: mixed}|list<mixed>|non-empty-string
```

Response shape:

```php
list<array{
  name: string,
  available: bool,
  reason: string|null
}>
```

### `info(string $name): array`

Returns host details, statuses, IP addresses, and timestamps.

Response shape:

```php
array{
  name: string|null,
  roid: string|null,
  statuses: list<string>,
  ipv4: list<string>,
  ipv6: list<string>,
  clientId: string|null,
  createClientId: string|null,
  updateClientId: string|null,
  createDate: \DateTimeImmutable|null,
  updateDate: \DateTimeImmutable|null,
  transferDate: \DateTimeImmutable|null
}
```

### `create(string|array $request, ?string $ipv4 = null, ?string $ipv6 = null): array`

Creates a host.

Supports full payload or simplified form:

```php
$host->create('ns1.example.rs', '192.0.2.10', null);
```

Response shape:

```php
array{name: string|null, createDate: \DateTimeImmutable|null}
```

### `update(array $request): array{}`

Updates addresses/statuses and/or renames host.

### `delete(string $name): array{}`

Deletes a host object.
