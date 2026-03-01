# Client API

`RNIDS\Client` is the main fluent entrypoint. It creates transport, connects, runs hello+login, and exposes service groups.

## Constructor

### `__construct(array $config)`

Creates and initializes an authenticated EPP session.

Required config keys:

- `host` (string)
- `username` (string)
- `password` (string)

Optional keys include `port`, timeouts, `language`, `version`, `objectUris`, `extensionUris`, and `tls` settings.

## Methods

### `transport(): Transport`

Returns the active transport instance.

### `close(): void`

Logs out (when logged in) and disconnects transport.

### `session(): SessionService`

Returns session command service.

### `domain(): DomainService`

Returns domain command service.

### `contact(): ContactService`

Returns contact command service.

### `host(): HostService`

Returns host command service.

### `responseMeta(): ?array`

Returns metadata from the latest parsed response in shape:

```php
array{
  clientTransactionId: string|null,
  message: string,
  resultCode: int,
  serverTransactionId: string|null
}|null
```
