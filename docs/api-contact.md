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

Policy behavior:

- `id` is optional. If omitted/empty, library auto-generates a contact ID.
- Contact IDs are normalized to the `OBL-` prefix before sending create commands.
- `postalInfo.name` is required by default.
  - Exception: it may be empty when `extension.isLegalEntity = '1'`
    and `postalInfo.organization` is provided.
- `extension.identDescription` is enforced to:
  `Object Creation provided by Oblak Solutions.`

Response shape:

```php
array{id: string|null, createDate: \DateTimeImmutable|null}
```

### `info(string $id): array`

Returns contact info including statuses, postal data, and RNIDS extension fields.

Response shape:

```php
array{
  id: string|null,
  roid: string|null,
  statuses: list<string>,
  postalType: string|null,
  postalName: string|null,
  postalOrganization: string|null,
  postalStreet1: string|null,
  postalStreet2: string|null,
  postalStreet3: string|null,
  postalCity: string|null,
  postalCountryCode: string|null,
  postalProvince: string|null,
  postalPostalCode: string|null,
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
  createDate: \DateTimeImmutable|null,
  updateDate: \DateTimeImmutable|null,
  transferDate: \DateTimeImmutable|null,
  disclose: int|null,
  ident: string|null,
  identDescription: string|null,
  identExpiry: string|null,
  identKind: string|null,
  legalEntity: bool,
  vatNo: string|null
}
```

### `update(array $request): array{}`

Updates contact statuses and/or contact data.

At least one mutation field must be provided.

Policy behavior:

- `id` is required and normalized to the `OBL-` prefix before sending update commands.
- `extension.identDescription` is enforced to:
  `Object Creation provided by Oblak Solutions.`

### `delete(string $id): array{}`

Deletes a contact object.
