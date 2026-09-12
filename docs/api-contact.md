# Contact Service API

`RNIDS\Contact\ContactService` exposes contact object commands.

## Methods

### `check(string|array $request): array`

Checks one or many contact IDs.

Request shape:

```php
array{ids: non-empty-list<non-empty-string>}|non-empty-list<non-empty-string>|non-empty-string
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
- `voice` must be a nonempty phone number. Missing, `null`, or empty voice fails
  locally; the development registry returned `2400` when voice was omitted.
- `postalInfo.name` is required by default.
  - Exception: it may be empty when `extension.isLegalEntity = '1'`
    and `postalInfo.organization` is provided.
- `extension.identDescription` is sent exactly as supplied; omission leaves it absent.

Response shape:

```php
array{id: string|null, createDate: \DateTimeImmutable|null}
```

The create payload includes a phone number, email, and postal information; the RNIDS extension
is optional and its string values are supplied by the caller:

```php
$contact = $client->contact()->create([
    'email' => 'person@example.rs',
    'voice' => '+381.111234567',
    'postalInfo' => [
        'type' => 'loc', // Optional; defaults to 'loc'. Also accepts the string 'int'.
        'name' => 'Person Example',
        'organization' => 'Example Company', // Optional.
        'address' => [
            'streets' => ['Main 1'],
            'city' => 'Belgrade',
            'countryCode' => 'RS',
            'province' => 'Belgrade', // Optional.
            'postalCode' => '11000', // Optional.
        ],
    ],
    'extension' => [
        'ident' => '12345678',
        'identDescription' => 'Company registration number',
        'isLegalEntity' => '1',
    ],
]);
```

Other optional create keys are `fax`, `authInfo`, and `disclose` (`0` or `1`).
Extension keys are `ident`, `identDescription`, `identExpiry`, `identKind`,
`isLegalEntity`, and `vatNo`. Supplied optional create strings must be nonempty;
use omission or `null` for unavailable values. `disclose` postal entries use the
same `type` as `postalInfo`.

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

- `id` is required and sent literally, matching `info()` and `delete()`. Use the
  identifier returned by `create()` when updating a newly created contact.
- Omitted or `null` optional values leave the registry value unchanged. Set `fax`
  or extension `ident`, `identDescription`, or `vatNo` to `''` to send an empty
  element and clear it; these fields were verified against the development registry.
- `voice`, postal `organization`, address `province`, and `postalCode` must be
  nonempty when supplied. RNIDS rejects clearing voice because phone is mandatory;
  empty organization/province/postal-code updates were accepted but left the old
  values unchanged. The library rejects these unsupported clearing requests before
  sending them. Omit the field, pass `null`, or supply a nonempty replacement.
- Empty `authInfo` is submitted and was accepted by the registry, but contact info
  does not expose its value, so its resulting state has not been verified. Email,
  city, country code, street values, and typed extension fields `identExpiry`,
  `identKind`, and `isLegalEntity` must remain nonempty when supplied.
- Supply the full address whenever changing any address field. Postal updates
  accept an empty `name` under the same legal-entity rule as creation: include
  `extension.isLegalEntity = '1'` and a nonempty `postalInfo.organization`.
- `extension.identDescription` is sent exactly as supplied. Omission or `null`
  leaves the existing description unchanged. Empty extension arrays do not
  count as a change.
- `disclose` accepts `0` or `1`. Disclosure name, organization, and address entries
  carry the postal `type` (`loc` or `int`); an update without `postalInfo` uses `loc`.

```php
$client->contact()->update([
    'id' => 'LEGACY-42',
    'fax' => '', // Remove the existing fax.
    'extension' => ['identDescription' => 'Updated identification record'],
]);
```

See the [verified registry behavior](registry-compatibility.md) and
[contact protocol reference](epp-protocol/epp-contact-commands.md) for clearing limits.
Disclosure attributes follow [RFC 5733, section 4](https://www.rfc-editor.org/rfc/rfc5733.html#section-4).

Request keys are `id`, `addStatuses`, `removeStatuses`, `postalInfo`, `voice`, `fax`,
`email`, `authInfo`, `disclose`, and `extension`. Status changes are lists of strings;
postal and extension structures match the create request above, with nullable
optional update values and the clearing semantics described above.

### `delete(string $id): array{}`

Deletes a contact object.
