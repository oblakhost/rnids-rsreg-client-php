# Session Service API

`RNIDS\Session\SessionService` implements protocol session lifecycle commands.

## Methods

### `hello(): array`

Sends EPP hello and returns server capabilities.

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

Authenticates a session.

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

### `poll(array $request = []): array`

Reads or acknowledges queue messages.

Request shape:

```php
array{messageId?: mixed, operation?: mixed}
```

`operation` supports:

- `request` (default) — fetch next queued message
- `ack` — acknowledge a specific message id (`messageId` is required)

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
