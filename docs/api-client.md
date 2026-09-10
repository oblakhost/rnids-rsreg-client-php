# Client API

`RNIDS\Client` is the main fluent entrypoint. It prepares transport/services and exposes session, domain, contact, and host groups.

## Constructor

### `__construct(array $config, ?Transport $transport = null)`

Creates a client instance and validates configuration.

The constructor does not perform network I/O. To authenticate the session, call `init()` or use `Client::ready($config)`.

Required config keys:

- `host` (string)
- `username` (string)
- `password` (string)

Native connections also require a `tls` configuration with `clientCertificatePath`.
The PEM contains the client certificate and private key. Optional TLS fields are
`clientCertificatePassword`, `caFilePath`, `peerName`, `allowSelfSigned`, `verifyPeer`,
and `verifyPeerName`. Peer and name verification use PHP's secure defaults when
omitted. Boolean settings require actual booleans; malformed TLS configuration throws.

Optional connection keys include `port`, `connectTimeoutSeconds`, `readTimeoutSeconds`,
`language`, `version`, `objectUris`, and `extensionUris`. Timeouts are integer seconds.
`allowPlaintext => true` explicitly permits native plaintext connections to local test
peers. Injected transports own their connection/security settings and do not require
native TLS configuration.

## Methods

### `init(): void`

Connects the transport, reads the unsolicited greeting, and waits for the actual login
response. Rejected authentication is thrown before initialization returns.

- Idempotent: repeated calls after successful initialization are no-ops.
- Required before calling `session()`, `domain()`, `contact()`, or `host()`.
- Reconnects after explicit logout or a fatal transport/protocol framing failure.
- Adds secDNS to default login extensions only when the greeting advertises it. An
  explicitly supplied `extensionUris` list is used as provided.

### `ready(array $config, ?Transport $transport = null): self`

Convenience factory that returns an already initialized client (`new Client($config)` + `init()`).

All service groups share a command executor and a transaction-ID generator unique to
that client instance. Replies must match the submitted `clTRID`; mismatches and damaged
frames disconnect the session. Cached service references cannot continue sending on an
invalidated session. Commands are never automatically retried after an uncertain outcome.

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

Metadata is cleared before each exchange or reconnect attempt. A transport failure,
malformed response, or transaction mismatch leaves null; a valid EPP error response
retains its own result code and transaction IDs. Local input validation performed
before an exchange leaves the previous response available.
