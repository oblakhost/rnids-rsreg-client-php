# Domain Service API

`RNIDS\Domain\DomainService` exposes domain object commands.

## Methods

### `check(string|array $request): array`

Checks one or many domain names.

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

### `info(string $name, ?string $hosts = null): array`

Returns domain info including statuses, contacts, nameservers, and RNIDS extension fields.

Response shape:

```php
array{
  name: string|null,
  roid: string|null,
  statuses: list<array{value: string, description: string|null}>,
  registrant: string|null,
  contacts: list<array{type: string, handle: string}>,
  nameservers: list<array{name: string, addresses: list<string>}>,
  clientId: string|null,
  createClientId: string|null,
  updateClientId: string|null,
  createDate: string|null,
  updateDate: string|null,
  expirationDate: string|null,
  extension: array{
    isWhoisPrivacy: string|null,
    operationMode: string|null,
    notifyAdmin: string|null,
    dnsSec: string|null,
    remark: string|null
  }
}
```

### `register(string|array $request, ...): array`

Registers a domain.

Supports full request payload and simplified fluent form:

```php
$domain->register($name, $registrant, $adminContact, $techContact, $nameservers, $years);
```

Response shape:

```php
array{name: string|null, createDate: string|null, expirationDate: string|null}
```

### `renew(string|array $request, ?int $years = null): array`

Renews a domain.

In simplified mode, current expiration date is resolved via `info()`.

Response shape:

```php
array{name: string|null, expirationDate: string|null}
```

### `delete(string $name): array{}`

Deletes a domain.

### `transfer(array $request): array`

Handles transfer lifecycle operations (`request|query|cancel|approve|reject`).

Response shape:

```php
array{
  name: string|null,
  transferStatus: string|null,
  requestClientId: string|null,
  requestDate: string|null,
  actionClientId: string|null,
  actionDate: string|null,
  expirationDate: string|null
}
```
