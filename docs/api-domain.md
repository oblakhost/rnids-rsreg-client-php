# Domain Service API

`RNIDS\Domain\DomainService` exposes domain object commands.

## Methods

### `check(string|array $request): array`

Checks one or many domain names.

### `info(string $name, ?string $hosts = null): array`

Returns domain info including statuses, contacts, nameservers, and RNIDS extension fields.

### `register(string|array $request, ...): array`

Registers a domain.

Supports full request payload and simplified fluent form:

```php
$domain->register($name, $registrant, $adminContact, $techContact, $nameservers, $years);
```

### `renew(string|array $request, ?int $years = null): array`

Renews a domain.

In simplified mode, current expiration date is resolved via `info()`.

### `delete(string $name): array{}`

Deletes a domain.

### `transfer(array $request): array`

Handles transfer lifecycle operations (`request|query|cancel|approve|reject`).
