# Library Architecture

The new implementation in `src/` is a **RNIDS/RSreg-specific** client, not a generic EPP framework.

## Architectural Goals

- Fluent API centered around discoverability:

```php
$client->domain()->register($request);
```

- Strongly typed DTOs and responses.
- Explicit RNIDS behaviors and fields.
- Clear separation of transport, XML construction, and operation services.
- Native stream transport as the default runtime model.

## Suggested Module Layout

```text
src/
├── Client.php
├── Builder.php
├── Connection/
│   ├── ConnectionConfig.php
│   ├── TlsConfig.php
│   ├── Transport.php
│   ├── NativeStreamTransport.php
│   └── EppFrameCodec.php
├── SocketClient.php
├── Session/
├── Domain/
├── Contact/
├── Host/
├── Extension/Rnids/
├── Xml/
└── Exception/
```

### Core Responsibilities

- `Client` is the fluent entry point and exposes sub-clients (`domain()`, `contact()`, `host()`, `session()`).
- `Connection/*` owns TCP/TLS, certificate config, framing, and low-level I/O using native PHP streams.
- `Xml/*` builds requests and parses responses with namespace-safe XPath usage.
- `Domain|Contact|Host|Session/*` implement operation-specific use-cases.
- `Extension/Rnids/*` contains RNIDS-only XML and DTO fields as first-class structures.

## Transport Strategy (Mandatory)

- Default transport implementation is native PHP streams (`stream_socket_client`) with deterministic EPP frame handling.
- EPP framing must stay in a dedicated codec (`EppFrameCodec`) using 4-byte network-order length prefix.
- TLS/certificate options must be modeled via typed config objects (`TlsConfig`) and applied through stream context options.
- Keep transport behind `Transport` to preserve testability and future adapter flexibility.
- **Do not use ReactPHP libraries in the core client implementation.**

## Fluent API Requirements

- Keep operation groups discoverable:
  - `$client->session()->hello()` / `login()` / `logout()` / `poll()`
  - `$client->domain()->check()` / `info()` / `register()` / `renew()` / `update()` / `delete()` / `transfer...`
  - `$client->contact()->create()` / `info()` / `update()` / `delete()`
  - `$client->host()->check()` / `info()` / `create()` / `update()` / `delete()`
- Public API method names should reflect RNIDS usage terminology.

## Design Constraints

- Do not introduce abstraction layers for unrelated registries.
- Keep RNIDS extension data as typed fields in DTOs (not opaque arrays).
- Use exceptions for transport/protocol failures and typed responses for successful operations.
- Keep the request lifecycle deterministic and synchronous in core (`connect` → `writeFrame` → `readFrame`).
