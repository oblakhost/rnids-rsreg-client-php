# Old Client Functional Analysis (`old-client/`)

This document inventories the legacy RNIDS EPP implementation retained in
`old-client/`. It is a source comparison reference, not current setup guidance or
evidence of live interoperability. The maintained 2.x implementation is in `src/`.
See the [archive policy](../old-client/README.md), [current API](api-reference.md),
and [verified registry behavior](registry-compatibility.md).

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

## 8) Maintained behavior coverage

The current implementation exposes the following operation groups. Its public
service methods use documented arrays; builders and parsers use typed DTOs
internally. Legacy class names and mutable DOM objects are not public 2.x APIs.

| Legacy responsibility | Maintained implementation |
| --- | --- |
| Hello, login, logout, poll | `src/Session/`; poll request and acknowledgment are explicit |
| Domain lifecycle | `src/Domain/`; check, info, register, renew, update, delete |
| Domain transfers | Request, query, approve, cancel, reject; compatibility aliases retained |
| Contact lifecycle | `src/Contact/`; check, create, info, update, delete with RNIDS validation |
| Host lifecycle | `src/Host/`; check, info, create, update, delete |
| RNIDS contact/domain extensions | Typed internal extension data and documented request fields |
| XML and EPP errors | Deterministic builders, namespace-safe parsers, typed exceptions and response metadata |
| Framing and transport | Dedicated frame codec, native streams, explicit TLS config |

The generic HTTP/HTTPS adapters, domain-claim objects, and undelete classes found
in the legacy tree are not supported fluent operations in 2.x. Their presence here
does not expand the current API contract. See [SUPPORT.md](../SUPPORT.md).

Unit coverage establishes request, parsing, and client behavior. The
[registry compatibility record](registry-compatibility.md) separately records
live outcomes and the remaining transfer, secure-mode, and poll acceptance work.

## 9) Old → New Responsibility Mapping

- `Protocols/EPP/eppConnection.php` -> `src/Connection/*` + low-level transport client
- `Protocols/EPP/eppRequests/*` -> `src/Xml/*` builders + operation request DTOs
- `Protocols/EPP/eppResponses/*` -> `src/Xml/*` parsers + typed response DTOs
- `Protocols/EPP/eppData/*` -> `src/Domain|Contact|Host/*` typed value objects
- `Protocols/EPP/eppExtensions/rnids-1.0/*` -> `src/Extension/Rnids/*`
- `Examples/*` -> new integration tests + usage docs

---

Retain this inventory and the original implementation for source comparison.
Apply fixes to the maintained client and its regression tests. Neither the legacy
examples nor old registry assumptions override the current documented API or
observed registry behavior.
