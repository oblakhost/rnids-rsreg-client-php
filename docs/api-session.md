# Session Service API

`RNIDS\Session\SessionService` implements protocol session lifecycle commands.

## Methods

### `hello(): array`

Sends EPP hello and returns server capabilities.

Client initialization obtains the greeting before sending login: it reads an
unsolicited greeting by default, or sends hello when `greetingMode` is `hello`.
The development endpoint requires the latter; see [client configuration](api-client.md).
Calling `hello()` explicitly sends a new hello request on the current session.
`receiveGreeting()` is the low-level operation for reading the initial greeting
without writing a command; it returns the same shape as `hello()`.

Response shape:

```php
array{
  extensionUris: list<string>,
  languages: list<string>,
  objectUris: list<string>,
  serverDate: string|null,
  serverId: string|null,
  versions: list<string>
}
```

### `login(array $request): array{}`

Authenticates a connected, unauthenticated session. Normal use through `Client`
performs login during `init()` or `ready()` using the client configuration;
the registry can reject duplicate login with result code `2002`.

Request shape:

```php
array{
  clientId: non-empty-string,
  password: non-empty-string,
  version?: non-empty-string,
  language?: non-empty-string,
  objectUris?: list<non-empty-string>,
  extensionUris?: list<non-empty-string>
}
```

### `logout(): array{}`

Ends session explicitly.

The transport is disconnected and the client's authenticated state is cleared. Call
`$client->init()` to reconnect; `close()` after an explicit logout is harmless.

### `poll(array $request = []): array`

Reads or acknowledges queue messages.

Request shape:

```php
array{messageId?: non-empty-string, operation?: 'req'|'ack'}
```

`operation` supports:

- `req` (default) — fetch next queued message
- `ack` — acknowledge a specific message id (`messageId` is required)

Reading leaves the message queued. Acknowledge its ID only after processing and
persisting the message. The development-registry record verifies read-only poll;
acknowledgment of an approved disposable message remains unverified. See the
[registry compatibility record](registry-compatibility.md).

Response shape:

```php
array{
  count: int|null,
  domainTransferData: array{
    actionClientId: string|null,
    actionDate: string|null,
    expirationDate: string|null,
    name: string|null,
    requestClientId: string|null,
    requestDate: string|null,
    transferStatus: string|null,
  }|null,
  message: string|null,
  messageId: string|null,
  queueDate: string|null
}
```

When poll response `resData` contains `domain:trnData`, `domainTransferData` is populated with
typed transfer fields. For queue-only responses without transfer payload, it is `null`.

Example (request/read mode with transfer payload):

```php
[
  'count' => 1,
  'messageId' => '154',
  'queueDate' => '2026-02-28T10:20:30.0Z',
  'message' => 'Transfer requested',
  'domainTransferData' => [
    'name' => 'example.rs',
    'transferStatus' => 'pending',
    'requestClientId' => 'requestor',
    'requestDate' => '2026-02-28T10:20:20.0Z',
    'actionClientId' => null,
    'actionDate' => null,
    'expirationDate' => '2027-02-28T10:20:20.0Z',
  ],
]
```
