# Registry compatibility record

This is the integration record of an independent third-party project unaffiliated
with RNIDS. Results describe the RNIDS development endpoint `epp-test.rnids.rs:700`;
they are not registry certification or evidence that every production workflow
has been exercised.

## September 12, 2026 validation

The client certificate was in date, its private key matched, and the server's CA
chain and hostname verified successfully. Tests authenticated using the existing
RNIDS test account. No TLS verification was disabled.

The full seven-case suite at `9992fac` passed six cases with **74 assertions**, no
failures or errors, and one skipped poll acknowledgment. Its nonzero exit came
from `--fail-on-skipped`. The automated DS lifecycle case accounts for 22 assertions.
Additional field and secure-mode probes ran at `33618b1` before applying the local
contact guards below. Tests created disposable resources and kept ownership
ledgers for pending deletions. The final run removed its child host; two domains
and three linked contacts remained pending registry deletion.

| Area | Observed result |
| --- | --- |
| Greeting, login, response handling | Authenticated successfully using explicit hello and acceptance of omitted response IDs; supplied IDs still checked |
| Contact lifecycle | Create, info, update, and delete passed; deleted contacts return `2303` to info even when their identifiers remain unavailable to check |
| Domain/host lifecycle | Registration, info/check, tech-contact replacement, renewal, child-host create/delete, and domain delete acceptance passed |
| DNSSEC DS | Registration with a DS record, info, add, selective removal, replacement using removeAll plus add, and removeAll returned `1000`; exact record values round-tripped |
| Secure mode | Normal to secure completed; secure to normal was accepted with `1000` but info retained secure mode and `pendingUpdate` |
| Read-only poll | Queue shape and result metadata passed |
| Poll acknowledgment | Skipped: the current head message was unrelated to the disposable resources, so it was not acknowledged |
| Full transfers | Requires two authorized registrar accounts and the external transfer-code/approval workflow; not verified end to end |

DS provisioning checks establish that the API stores and retrieves DS records.
They do not exercise authoritative nameserver signing or DNS resolver validation.

## Contact phone requirement and clearing

Creating an otherwise valid contact without `voice` returned `2400`, with a
server-side null-reference reason. A matching control request with a phone number
returned `1000`; both disposable identifiers were confirmed absent after cleanup.
The public create API now requires nonempty `voice` and rejects omission, `null`,
or an empty string before sending.

Each checked field was populated before an update. Tests inspected the resulting
contact info; diagnostic probes also inspected the raw response to distinguish
registry behavior from a mapping error.

| Update value | Result | Library behavior |
| --- | --- | --- |
| Omitted or `null` optional field | Existing value preserved | Leave unchanged |
| `fax: ''` | Accepted and cleared | Submit the empty element |
| Empty extension `ident`, `identDescription`, or `vatNo` | Accepted and cleared | Submit the empty element |
| `voice: ''` | `2003`, reason `Phone is mandatory field`; old voice retained | Reject before sending |
| Empty `postalInfo.organization` | `1000`, previous value retained | Reject before sending |
| Empty address `province` or `postalCode` | `1000`, previous value retained | Reject before sending |
| `authInfo: ''` | `1000`; value is absent from info both before and after | Submit, with resulting state unverified |

Use nonempty replacements for the fields that cannot be cleared. These are nested
request fields as documented in the [contact API](api-contact.md); flat postal
names such as `postalProvince` are response aliases.

## Deferred completion and cleanup

Domain deletion may return `1000` while info still shows `pendingDelete` or
`pendingUpdate`. Linked contacts can then fail deletion with `2305`. The live suite
records these owned resources as pending and removes standalone diagnostic contacts
and child hosts when permitted. Recheck the recorded resources after the registry
finishes deletion. A successful command response alone does not establish removal.

External approval of secure-mode changes, complete transfers between registrars,
and acknowledgment of an approved disposable queue message remain prerequisites
for the project's LTS designation. See [SUPPORT.md](../SUPPORT.md) for the maintenance
window and application recovery guidance.
