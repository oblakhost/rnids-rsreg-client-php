# Host Service API

`RNIDS\Host\HostService` exposes nameserver host commands.

## Methods

### `check(string|array $request): array`

Checks one or many host objects.

Request shape:

```php
array{names: non-empty-list<non-empty-string>}|non-empty-list<non-empty-string>|non-empty-string
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
$client->host()->create('ns1.example.rs', '192.0.2.10', '2001:db8::10');
```

Full request shape:

```php
array{
  name: non-empty-string,
  addresses?: list<array{
    address: non-empty-string,
    ipVersion?: 'v4'|'v6'
  }>|null
}
```

`ipVersion` defaults to `v4`; set it explicitly for IPv6 addresses. Addresses
are optional, subject to registry policy for the host. Use the array payload or
the positional form; positional IP arguments apply only when the first argument
is a hostname string.

Response shape:

```php
array{name: string|null, createDate: \DateTimeImmutable|null}
```

### `update(array $request): array{}`

Updates addresses/statuses and/or renames a host.

```php
array{
  name: non-empty-string,
  add?: array{
    addresses?: list<array{address: non-empty-string, ipVersion?: 'v4'|'v6'}>|null,
    statuses?: list<non-empty-string>|null
  }|null,
  remove?: array{
    addresses?: list<array{address: non-empty-string, ipVersion?: 'v4'|'v6'}>|null,
    statuses?: list<non-empty-string>|null
  }|null,
  newName?: non-empty-string|null
}
```

Provide at least one of `add`, `remove`, or `newName`. Each supplied add/remove
section must contain at least one address or status. Omission or `null` leaves
that section unchanged. Status values must be supported by RNIDS.

```php
$client->host()->update([
    'name' => 'ns1.example.rs',
    'add' => ['addresses' => [['address' => '2001:db8::10', 'ipVersion' => 'v6']]],
    'remove' => ['addresses' => [['address' => '192.0.2.10', 'ipVersion' => 'v4']]],
]);
```

Successful update and delete calls return an empty array; `responseMeta()`
provides the EPP result code, including whether registry action remains pending.

### `delete(string $name): array{}`

Deletes a host object.
