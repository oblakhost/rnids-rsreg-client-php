# Client API

`RNIDS\Client` is the main fluent entrypoint. It prepares transport/services and exposes session, domain, contact, and host groups.

## Constructor

### `__construct(array $config)`

Creates a client instance and validates configuration.

The constructor does not perform network I/O. To authenticate the session, call `init()` or use `Client::ready($config)`.

Required config keys:

- `host` (string)
- `username` (string)
- `password` (string)

Optional keys include `port`, timeouts, `language`, `version`, `objectUris`, `extensionUris`, and `tls` settings.

## Methods

### `init(): void`

Connects the transport and performs `hello` + `login`.

- Idempotent: repeated calls after successful initialization are no-ops.
- Required before calling `session()`, `domain()`, `contact()`, or `host()`.

### `ready(array $config): self`

Convenience factory that returns an already initialized client (`new Client($config)` + `init()`).

### `transport(): Transport`

Returns the active transport instance.

### `close(): void`

Logs out (when logged in) and disconnects transport.

- Explicit calls can throw when logout/disconnect fails.
- Destructor path never throws, but diagnostics are still captured.

### `lastCloseError(): ?\Throwable`

Returns the last captured shutdown error from `close()` or destructor cleanup.

### `session(): SessionService`

Returns session command service.

### `domain(): DomainService`

Returns domain command service.

### `contact(): ContactService`

Returns contact command service.

### `host(): HostService`

Returns host command service.

Service access methods throw `RuntimeException` when called before initialization:

`Client is not initialized. Call init() first or use Client::ready().`

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
