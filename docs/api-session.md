# Session Service API

`RNIDS\Session\SessionService` implements protocol session lifecycle commands.

## Methods

### `hello(): array`

Sends EPP hello and returns server capabilities.

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
