# Old Client Functional Analysis (`old-client/`)

This document captures the functional behavior of the legacy RNIDS EPP client and serves as a migration reference for the new implementation in `src/`.

## 1) High-Level Layout

```text
old-client/
├── Examples/                        # CLI usage flows (behavior reference)
├── Protocols/EPP/                   # Core transport, request/response, data objects
│   ├── eppConnection.php
│   ├── eppHttpConnection.php
│   ├── eppHttpsConnection.php
│   ├── eppException.php
│   ├── eppData/
│   ├── eppRequests/
│   ├── eppResponses/
│   └── eppExtensions/rnids-1.0/
│       ├── eppRequests/
│       └── eppResponses/
├── Registries/rnidsEppConnection/   # RNIDS-specific connection class
├── autoloader.php                   # Legacy autoload wiring
└── README                           # RNIDS connection/certificate notes
```

## 2) Bootstrapping and Runtime Model

### `autoloader.php`
- Registers two autoloaders:
  - `autoloadEPP` for protocol/data/request/response classes
  - `autoloadRegistry` for registry-specific connection classes
- Loads `Examples/base.php` and sets timezone (`UTC`).
- Uses OS-specific path handling (Windows vs non-Windows).

### `README` operational notes (RNIDS specific)
- Production connectivity requires client certificate during connection.
- Certificate conversion from `.pfx` to `.pem` is documented.
- Optional CA/root certificate handling is documented.
- RNIDS connection setup requires hostname, username, password, port, client cert, CA cert.

## 3) Transport and Session Foundation

## Core connection classes

### `Protocols/EPP/eppConnection.php`
Primary EPP transport implementation.

Key behavior:
- Opens socket connection (TCP/TLS depending on config).
- Implements EPP frame protocol (4-byte length-prefixed messages).
- Sends request XML and reads response XML.
- Handles XML namespace setup and command metadata injection.
- Supports login/session context fields, language/version/services/extensions.
- Supports certificate-related SSL context configuration.

Typical lifecycle in examples:
1. connect
2. hello (greeting)
3. login
4. operation command(s)
5. logout
6. disconnect

### `Protocols/EPP/eppHttpConnection.php` / `eppHttpsConnection.php`
- HTTP/HTTPS transport variants around the same logical EPP request/response model.

### `Registries/rnidsEppConnection/eppConnection.php`
- RNIDS registry specialization of base connection behavior.
- Encapsulates RNIDS defaults and certificate/TLS expectations.

### `Protocols/EPP/eppException.php`
- Legacy exception type used for protocol/connection failure paths.

## 4) Supported Functional Operations (Legacy Coverage)

All major EPP groups are implemented and demonstrated in `Examples/`.

## Session
- Hello / Greeting
- Login
- Logout
- Poll

Related examples:
- `Examples/base.php` (hello/login/logout helpers)
- `Examples/poll.php`

## Domain
- Check: `checkdomain.php`
- Info: `infodomain.php`
- Create/Register: `registerdomain.php`
- Renew: `renewdomain.php`
- Update/Modify: `modifydomain.php`
- Delete: `deletedomain.php`
- Transfer flows:
  - Request: `transferdomain.php`
  - Query: `transferquery.php`
  - Approve/Confirm: `transferconfirm.php`
  - Reject: `transferreject.php`

## Contact
- Create: `createcontact.php`
- Info: `infocontact.php`
- Update: `updatecontact.php`
- Delete: `deletecontact.php`

## Host
- Check: `checkhost.php`
- Info: `infohost.php`
- Create: `createhost.php`
- Update: `eppUpdateHostRequest` support (operation class present)
- Delete: `deletehost.php`

## 5) Request/Response Class Coverage

## Base request classes (`Protocols/EPP/eppRequests/`)
- Request framework: `eppRequest`, `eppCreateRequest`, `eppUpdateRequest`
- Session: `eppHelloRequest`, `eppLoginRequest`, `eppLogoutRequest`, `eppPollRequest`
- Domain/host/contact operations:
  - `eppCheckRequest`
  - `eppInfoDomainRequest`, `eppInfoContactRequest`, `eppInfoHostRequest`
  - `eppCreateDomainRequest`, `eppCreateContactRequest`, `eppCreateHostRequest`
  - `eppUpdateDomainRequest`, `eppUpdateContactRequest`, `eppUpdateHostRequest`
  - `eppDeleteRequest`
  - `eppRenewRequest`
  - `eppTransferRequest`
  - `eppUndeleteRequest`

## Base response classes (`Protocols/EPP/eppResponses/`)
- Response framework: `eppResponse`
- Session responses: `eppHelloResponse`, `eppLoginResponse`, `eppLogoutResponse`, `eppPollResponse`
- Operation responses:
  - `eppCheckResponse`
  - `eppInfoResponse`, `eppInfoDomainResponse`, `eppInfoContactResponse`, `eppInfoHostResponse`
  - `eppCreateResponse`
  - `eppUpdateResponse`, `eppUpdateDomainResponse`, `eppUpdateContactResponse`, `eppUpdateHostResponse`
  - `eppDeleteResponse`
  - `eppRenewResponse`
  - `eppTransferResponse`
  - `eppUndeleteResponse`

Notable behavior:
- `eppResponse` defines a broad set of EPP result code constants and helper methods for success/result handling.
- Derived response classes parse operation-specific nodes via DOM/XPath.

## 6) Data Model Objects (`Protocols/EPP/eppData/`)

Legacy object layer used as request payload primitives:
- `eppContact`
- `eppContactHandle`
- `eppContactPostalInfo`
- `eppDomain`
- `eppDomainClaim`
- `eppHost`
- `eppIDNA`

These classes represent domain/contact/host identities, contact postal and disclosure data, and domain-related attributes needed by request builders.

## 7) RNIDS Extension Coverage (`eppExtensions/rnids-1.0/`)

RNIDS extension request classes:
- `rnidsEppCreateContactRequest`
- `rnidsEppUpdateContactRequest`
- `rnidsEppCreateDomainRequest`
- `rnidsEppUpdateDomainRequest`

RNIDS extension response classes:
- `rnidsEppInfoContactResponse`
- `rnidsEppInfoDomainResponse`

`includes.php` wires extension class includes for legacy runtime.

Practical meaning for migration:
- RNIDS-specific contact/domain fields are already first-class in behavior and must remain first-class in the new client API and DTOs.

## 8) Functional Parity Checklist for New `src/` Client

To preserve old-client behavior, the new fluent client should fully cover:
- Session: hello, login, logout, poll
- Domain: check, info, register(create), renew, update, delete, transfer flows (request/query/approve/reject)
- Contact: create, info, update, delete
- Host: check, info, create, update, delete
- RNIDS extension handling for domain/contact operations
- EPP result code handling and exception mapping
- Deterministic XML generation + namespace-safe parsing
- EPP frame protocol (length prefix) and TLS/certificate support

## 9) Old → New Responsibility Mapping

- `Protocols/EPP/eppConnection.php` -> `src/Connection/*` + low-level transport client
- `Protocols/EPP/eppRequests/*` -> `src/Xml/*` builders + operation request DTOs
- `Protocols/EPP/eppResponses/*` -> `src/Xml/*` parsers + typed response DTOs
- `Protocols/EPP/eppData/*` -> `src/Domain|Contact|Host/*` typed value objects
- `Protocols/EPP/eppExtensions/rnids-1.0/*` -> `src/Extension/Rnids/*`
- `Examples/*` -> new integration tests + usage docs

---

This document should be treated as a behavior inventory, not a design constraint on old architecture style. The new implementation should preserve capability while moving to strict types, DTO-driven APIs, fluent services, and RNIDS-first domain modeling.
