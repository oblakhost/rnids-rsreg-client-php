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
