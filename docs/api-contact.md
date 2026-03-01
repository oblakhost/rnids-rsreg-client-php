# Contact Service API

`RNIDS\Contact\ContactService` exposes contact object commands.

## Methods

### `check(string|array $request): array`

Checks one or many contact IDs.

Request shape:

```php
array{ids?: mixed}|list<mixed>|non-empty-string
```

Response shape:

```php
list<array{
  id: string,
  available: bool,
  reason: string|null
}>
```

### `create(array $request): array`

Creates a contact object.

Response shape:

```php
array{id: string|null, createDate: string|null}
```

### `info(string $id): array`

Returns contact info including statuses, postal data, and RNIDS extension fields.

Response shape:

```php
array{
  id: string|null,
  roid: string|null,
  statuses: list<array{value: string, description: string|null}>,
  postalInfo: array{
    type: string,
    name: string,
    organization: string|null,
    address: array{
      streets: list<string>,
      city: string,
      countryCode: string,
      province: string|null,
      postalCode: string|null
    }
  }|null,
  voice: string|null,
  fax: string|null,
  email: string|null,
  clientId: string|null,
  createClientId: string|null,
  updateClientId: string|null,
  createDate: string|null,
  updateDate: string|null,
  transferDate: string|null,
  disclose: int|null,
  extension: array{
    ident: string|null,
    identDescription: string|null,
    identExpiry: string|null,
    identKind: string|null,
    isLegalEntity: string|null,
    vatNo: string|null
  }
}
```

### `update(array $request): array{}`

Updates contact statuses and/or contact data.

At least one mutation field must be provided.

### `delete(string $id): array{}`

Deletes a contact object.
