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
  statuses: list<string>,
  registrant: string|null,
  adminContact: string|null,
  techContact: string|null,
  nameservers: array<string, array{ipv4: list<string>, ipv6: list<string>}>,
  clientId: string|null,
  createClientId: string|null,
  updateClientId: string|null,
  createDate: \DateTimeImmutable|null,
  updateDate: \DateTimeImmutable|null,
  expirationDate: \DateTimeImmutable|null,
  whoisPrivacy: bool,
  operationMode: string|null,
  notifyAdmin: bool,
  dnsSec: bool,
  remark: string|null
}
```

### `register(string|array $request, ?string $registrant = null, ?string $adminContact = null, ?string $techContact = null, string|array|null $nameservers = null, ?int $years = 1, ?string $authInfo = null, ?array $extension = null): array`

Registers a domain.

Supports full request payload and simplified fluent form.

Full request shape:

```php
array{
  name?: mixed,
  period?: mixed,
  periodUnit?: mixed,
  nameservers?: mixed,
  registrant?: mixed,
  contacts?: mixed,
  authInfo?: mixed,
  extension?: array{
    isWhoisPrivacy?: mixed,
    operationMode?: mixed,
    notifyAdmin?: mixed,
    dnsSec?: mixed,
    remark?: mixed
  }|mixed
}|non-empty-string
```

Simplified form:

```php
$domain->register(
    'example.rs',
    'REG-123',
    'OBL-admin',
    'OBL-tech',
    ['ns1.example.rs', 'ns2.example.rs'],
    1,
    'auth-code-123',
    [
        'isWhoisPrivacy' => true,
        'operationMode' => 'direct',
        'notifyAdmin' => false,
        'dnsSec' => true,
        'remark' => 'Created via API',
    ],
);
```

Response shape:

```php
array{name: string|null, createDate: \DateTimeImmutable|null, expirationDate: \DateTimeImmutable|null}
```

### `renew(string|array $request, ?int $years = null): array`

Renews a domain.

In simplified mode, current expiration date is resolved via `info()`.

Response shape:

```php
array{name: string|null, expirationDate: \DateTimeImmutable|null}
```

### `update(array $request): array{}`

Updates an existing domain object.

Common use case:

- Reassigning admin/tech contact handles via `add.contacts` / `remove.contacts`.

Request shape:

```php
array{
  name: mixed,
  add?: array{
    contacts?: list<array{type: 'admin'|'tech'|'billing', handle: non-empty-string}>,
    statuses?: list<non-empty-string>
  },
  remove?: array{
    contacts?: list<array{type: 'admin'|'tech'|'billing', handle: non-empty-string}>,
    statuses?: list<non-empty-string>
  },
  registrant?: mixed,
  authInfo?: mixed,
  extension?: array{
    remark?: mixed,
    isWhoisPrivacy?: mixed,
    operationMode?: mixed,
    notifyAdmin?: mixed,
    dnsSec?: mixed
  }|mixed
}
```

At least one mutation key must be present: `add`, `remove`, `registrant`, or `authInfo`.

### `delete(string $name): array{}`

Deletes a domain.

### `transfer(string $domain, string $transferCode): array`

Approves a domain transfer using the provided transfer code.

### `getCode(string $domain): array`

Runs transfer operation `request` for the domain.

### `getState(string $domain): array`

Runs transfer operation `query` for the domain.

Response shape:

```php
array{
  name: string|null,
  transferStatus: string|null,
  requestClientId: string|null,
  requestDate: \DateTimeImmutable|null,
  actionClientId: string|null,
  actionDate: \DateTimeImmutable|null,
  expirationDate: \DateTimeImmutable|null
}
```
