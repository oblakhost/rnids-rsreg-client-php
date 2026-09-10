# Domain Service API

`$client->domain()` returns `RNIDS\Domain\DomainService`. Public methods accept and
return arrays; requests and XML responses use typed DTOs internally. Dates in
results are `DateTimeImmutable|null`. Invalid request values raise
`InvalidArgumentException` (PHP parameter type mismatches raise `TypeError`).

## Check and info

`check(string|array $request): array` accepts a domain string, a nonempty list of
strings, or `['names' => [...]]`. It returns
`list<array{name: string, available: bool, reason: string|null}>`.

`info(string $name, ?string $hosts = null): array` accepts `all` (default), `del`
(delegated nameservers), `sub` (subordinate hosts), or `none`. The registry controls
which host data is returned. `hosts` and `nameservers` preserve distinct fields:

```php
array{
  name: string|null,
  roid: string|null,
  statuses: list<string>,
  registrant: string|null,
  adminContact: string|null,
  techContact: string|null,
  nameservers: array<string, array{ipv4: list<string>, ipv6: list<string>}>,
  hosts: list<string>,
  clientId: string|null,
  createClientId: string|null,
  updateClientId: string|null,
  createDate: DateTimeImmutable|null,
  updateDate: DateTimeImmutable|null,
  expirationDate: DateTimeImmutable|null,
  whoisPrivacy: bool,
  isDomainVerified: bool,
  domainVerifiedOn: DateTimeImmutable|null,
  domainVerificationRequestExpiresOn: DateTimeImmutable|null,
  isWhoisPrivacyPaid: bool,
  whoisPrivacyPaidUntil: DateTimeImmutable|null,
  operationMode: string|null,
  notifyAdmin: bool,
  dnsSec: bool,
  dnssec: array{records: list<array{keyTag: int, alg: int, digestType: int, digest: string}>},
  remark: string|null
}
```

## Register

```php
register(
    string|array $request,
    ?string $registrant = null,
    ?string $adminContact = null,
    ?string $techContact = null,
    string|array|null $nameservers = null,
    ?int $years = 1,
    ?string $authInfo = null,
    ?array $extension = null,
): array
```

The full array accepts:

```php
array{
  name: non-empty-string,
  registrant: non-empty-string,
  contacts: non-empty-list<array{type: 'admin'|'tech'|'billing', handle: non-empty-string}>,
  period?: positive-int|null,
  periodUnit?: 'y'|'m',
  nameservers?: list<array{
    name: non-empty-string,
    addresses?: list<non-empty-string|array{address: non-empty-string, ipVersion: 'v4'|'v6'}>
  }>,
  authInfo?: non-empty-string|null,
  extension?: array{
    isWhoisPrivacy?: bool|null,
    operationMode?: 'normal'|'secure'|null,
    notifyAdmin?: bool|null,
    dnsSec?: bool|null,
    remark?: non-empty-string|null
  }|null,
  dnssec?: array{records: non-empty-list<array{keyTag: int, alg: int, digestType: int, digest: string}>}|null
}
```

Both admin and tech contacts are required. Array registration defaults the period
unit to `y`; omitting `period` omits it from XML. When `$request` is an array, the
other method arguments are unused. DNSSEC provisioning uses this full array form.

The simplified string form requires the registrant, admin and tech identifiers,
and at least one nameserver. Nameservers may be one hostname, a list of hostnames,
or the detailed arrays above. IP version is inferred for string addresses; a
structured address must specify `ipVersion`. Positive `$years` defaults to 1.

```php
$client->domain()->register(
    'example.rs',
    'REG-123',
    'ADM-123',
    'TEC-123',
    ['ns1.example.rs', 'ns2.example.rs'],
    1,
    'auth-code-123',
    ['isWhoisPrivacy' => true, 'operationMode' => 'normal'],
);
```

Result: `array{name: string|null, createDate: DateTimeImmutable|null, expirationDate: DateTimeImmutable|null}`.

## Renew and delete

`renew(string $domain, int $years = 1, null|string|DateTimeInterface $expiry = null): array`
renews for 1–10 years. Omit `$expiry` to fetch current expiration through `info()`;
otherwise supply the current registry date (`Y-m-d` or an ISO timestamp whose first
10 characters contain that date), or a date object. Result:
`array{domain: string, expiryDate: DateTimeImmutable|null}`.

`delete(string $name): array{}` returns an empty array on success.

## Update

`update(array $request): array{}` returns an empty array on success. Required `name`
is a nonempty domain string. Optional `add` and `remove` sections accept:

```php
array{
  contacts?: list<array{type: 'admin'|'tech'|'billing', handle: non-empty-string}>,
  statuses?: list<non-empty-string>,
  nameservers?: non-empty-string|list<non-empty-string|array{
    name: non-empty-string,
    addresses?: list<non-empty-string|array{address: non-empty-string, ipVersion: 'v4'|'v6'}>
  }>
}
```

A supplied section must contain a change. Unknown top-level, section, and extension
keys are rejected. Top-level `registrant` and `authInfo` accept nonempty strings
or null (omitted). `extension` accepts the register fields; its `remark` additionally
accepts an empty string to clear it. `operationMode` accepts `normal` or `secure` on both registration and update. An extension-only change is valid;
null values and an empty extension are not changes. RNIDS ignores the legacy
`extension.dnsSec` flag: it does not provision DNSSEC.

```php
$client->domain()->update([
    'name' => 'example.rs',
    'add' => ['nameservers' => [
        ['name' => 'ns2.example.rs', 'addresses' => ['192.0.2.2', '2001:db8::2']],
    ]],
    'remove' => ['nameservers' => ['ns1.example.rs']],
]);
$client->domain()->update([
    'name' => 'example.rs',
    'extension' => ['isWhoisPrivacy' => true],
]);
```

RNIDS gives registrant changes precedence over other mutations. The client
rejects mixed registrant requests; submit the registrant change separately. Secure mode can produce pending changes. Consult
[RNIDS update processing rules](epp-protocol/epp-domain-commands.md#rsreg2-specific-processing-rules).

## DNSSEC DS records

The implementation uses the DS interface of
[RFC 5910, secDNS 1.1](https://www.rfc-editor.org/rfc/rfc5910.html), constrained by
the [checked-in RNIDS reference](epp-protocol/epp-domain-commands.md). It has offline
XML and behavior tests; live RNIDS acceptance has not been verified.

Each record has integer `keyTag` (0–65535), `alg` (3, 5, 6, 7, 8, 10, 13, 14),
`digestType` (1, 2, 3, 4), and hexadecimal `digest` (40, 64, 64, or 96 characters,
respectively). These accepted protocol values do not recommend a cryptographic
algorithm; use DS data supplied by your DNS operator.

Register with `dnssec => ['records' => [$ds]]`. Info returns the same record shape
under `dnssec.records`, defaulting to an empty list when absent. Update supports
`dnssec => ['add' => [$ds], 'remove' => [$oldDs], 'removeAll' => false]`, with each
key optional. At least one change is required. `removeAll => true` cannot coexist
with nonempty `remove`, but can accompany `add` to replace all records. Removal
XML precedes additions. `keyData`, `urgent`, and `maxSigLife` are unsupported.

```php
$client->domain()->update(['name' => 'example.rs', 'dnssec' => ['add' => [$ds]]]);
$client->domain()->update(['name' => 'example.rs', 'dnssec' => ['removeAll' => true]]);
```

DNSSEC updates cannot include base-domain or RNIDS extension changes: RNIDS would
ignore them, so the client rejects the mixed request. Use separate calls.

## Transfers

The explicit methods correspond directly to EPP transfer operations:

```php
transferRequest(string $domain, ?string $authInfo = null): array
transferQuery(string $domain, ?string $authInfo = null): array
transferApprove(string $domain, ?string $authInfo = null): array
transferCancel(string $domain, ?string $authInfo = null): array
transferReject(string $domain, ?string $authInfo = null): array
```

Optional authorization strings must be nonempty when provided. Existing methods
remain compatible: `getCode($domain)` sends `request`, `getState($domain)` sends
`query`, and `transfer($domain, $transferCode)` sends `approve` with a required
nonempty code. `getCode()` does not return a generated transfer code; it maps the
registry transfer response. Each method returns:

```php
array{
  name: string|null,
  transferStatus: string|null,
  requestClientId: string|null,
  requestDate: DateTimeImmutable|null,
  actionClientId: string|null,
  actionDate: DateTimeImmutable|null,
  expirationDate: DateTimeImmutable|null
}
```
