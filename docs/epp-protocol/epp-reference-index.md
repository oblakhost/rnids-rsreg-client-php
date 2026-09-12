# RsReg2 / RNIDS EPP Reference Index

This documentation set summarizes `EPP-commands.txt` (RsReg2 EPP Commands, v1.2),
the historical source document, which is not distributed in this repository.
It is maintained by an independent third-party project unaffiliated with RNIDS.
Protocol descriptions do not establish SDK support or verified registry acceptance;
consult the [API reference](../api-reference.md) for implemented operations and the
[registry compatibility record](../registry-compatibility.md) for live evidence.

## Scope

- Registry profile: RNIDS / RsReg2
- Protocol base: EPP (RFC 5730/5731/5732/5733/5734)
- Additional coverage:
  - DNSSEC extension (`secDNS-1.1`)
  - RNIDS domain/contact extensions
  - Finance info object (protocol reference only; not implemented by the SDK or CLI)
  - Poll message model

## Core Namespaces

- EPP: `urn:ietf:params:xml:ns:epp-1.0`
- Domain: `urn:ietf:params:xml:ns:domain-1.0`
- Contact: `urn:ietf:params:xml:ns:contact-1.0`
- Host: `urn:ietf:params:xml:ns:host-1.0`
- Finance: `urn:ietf:params:xml:ns:finance-1.0`
- secDNS: `urn:ietf:params:xml:ns:secDNS-1.1`
- RNIDS login extension URI: `http://www.rnids.rs/epp/xml/rnids-1.0`
- RNIDS domain extension: `http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0`
- RNIDS contact extension: `http://www.rnids.rs/epp/xml/contact-rnids-ext-1.0`

## Document Map

- [Session Commands](./epp-session-commands.md)
- [Domain Commands](./epp-domain-commands.md)
- [Contact Commands](./epp-contact-commands.md)
- [Host Commands](./epp-host-commands.md)
- [Finance and Poll](./epp-finance-and-poll.md)
- [RNIDS Extensions](./epp-rnids-extensions.md)

## EPP Result Code Summary

Common result codes seen across RsReg2 operations:

- `1000` — Command completed successfully
- `1001` — Command completed successfully; action pending
- `1300` — Command completed successfully; no messages
- `1301` — Command completed successfully; ack to dequeue
- `1500` — Command completed successfully; ending session
- `2001` — Command syntax error
- `2003` — Required parameter missing
- `2005` — Parameter value syntax/range error
- `2102` — Unimplemented option
- `2200` — Authentication error
- `2201` — Authorization error
- `2202` — Invalid authorization information
- `2301` — Object not pending transfer
- `2302` — Object exists
- `2303` — Object does not exist
- `2304` — Object status prohibits operation
- `2305` — Object association prohibits operation
- `2306` — Parameter value policy error
- `2400` — Command failed
- `2500/2501/2502` — Server-side closing/session limit errors

## Practical Notes for This Client

- Keep request XML deterministic and namespace-explicit.
- Include `clTRID` on command requests.
- Public services accept and return shaped arrays; RNIDS extension fields use
  typed DTO properties internally.
- For transfer and secure-mode updates, follow RsReg2-specific behaviors described in command docs.
