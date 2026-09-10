# Library audit — 2026-09-10

This is the original audit snapshot. Subsequent offline fixes and compatibility notes
are recorded in [the remediation report](2026-09-10-remediation.md).

The library has a useful separation between services, XML handling, and native
transport, but passing tests currently overstate its operational reliability.
The highest priorities are session response alignment, TLS configuration, domain
nameserver updates, and preserving contact identifiers during updates.

Reviewed the working tree based on `dc04f59`, including its existing local API
documentation and Composer changes. No implementation fixes were made. Existing
uncommitted user changes were preserved. Findings below distinguish reproduced
behavior, protocol/schema evidence, and design criticism.

## Test results

| Check | Result |
| --- | --- |
| `composer test`, PHP 8.2.31 | Passed: 275 tests, 843 assertions; PHPStan and PHPCS passed |
| `composer validate --strict` | Passed |
| `composer test:coverage`, default PHP 8.2 | Could not collect coverage: no coverage driver installed for this runtime |
| `PATH=/home/linuxbrew/.linuxbrew/opt/php@8.1/bin:$PATH composer test:coverage` | Passed on PHP 8.1.34 / PCOV 1.0.12: 275 tests, 843 assertions; **90.51%** line coverage, 2777/3068 lines, against the 90% gate |
| `composer test:live -- --display-skipped` | All 8 tests skipped; 0 assertions; exit status 0 |
| Live suite outside the network sandbox | Same result: 8 skipped |
| Live suite outside sandbox with `RNIDS_EPP_CLIENT_CERT_PATH=tests/fixtures/oblak.pem` | Same result: 8 skipped |
| Independent TCP probe with 10-second timeout | `epp-test.rnids.rs:700` resolved but timed out, errno 110 |
| Local synthetic EPP server with real `NativeStreamTransport` | Reproduced successful initialization despite rejected login, followed by responses attributed to the wrong commands |

The first local quality-gate attempt hit a sandbox restriction on PHPStan's
localhost worker socket. Re-running the same gate outside the sandbox passed.
This was an environment failure, not a PHPStan finding.

The live suite never reached TLS or EPP authentication. The real private key loads
with the configured passphrase and matches the real certificate, but that
certificate expired at **2026-07-19 12:16:02 UTC**. The default discovery path also
selects the tracked `dummy-client-cert.pem` ahead of the real certificate; OpenSSL
cannot parse the dummy as an X.509 certificate. These are separate obstacles from
the current TCP timeout. No claim is made about whether RNIDS accepts the account
credentials or the certificate at present.

## Findings

### 1. P1 — Initialization consumes responses in the wrong order

Issue: `rnids-epp-client-fhl`.

[Client.php](../../src/Client.php), lines 119–128, connects and immediately sends
`hello()`. The transport has not consumed the unsolicited connection greeting.
The hello call therefore reads the initial greeting, and login reads the reply to
hello. [ResponseMetadataParser.php](../../src/Xml/Response/ResponseMetadataParser.php)
accepts greetings as synthetic successful responses, and the login parser only
wraps metadata. Initialization declares the session authenticated before reading
the actual login response.

This was reproduced both with a queued transport and with a local TCP EPP server
using the real client and native transport:

```text
Server rejects login with 2200.
Client::ready() returns metadata: resultCode=1000, message=Greeting, clTRID=null.
The next poll() raises AuthenticationFailure, code 2200.
close() receives the response to poll instead of logout.
```

The initial greeting is required by
[RFC 5734, section 2](https://www.rfc-editor.org/rfc/rfc5734.html#section-2).
RNIDS live behavior could not be checked because TCP connectivity failed.

[CommandExecutor.php](../../src/Xml/CommandExecutor.php), lines 35–42, also never
compares response `clTRID` with the request. A synthetic response carrying
`UNRELATED-COMMAND` was accepted for `EXPECTED-ID`. Consume the connection greeting
once, distinguish greetings from command responses, and correlate commands with
their responses. The current lifecycle unit fixture omits the extra greeting,
which conceals this failure.

### 2. P1 — Invalid TLS settings silently select plaintext TCP

Issue: `rnids-epp-client-zg2`.

[ClientConfigFactory.php](../../src/Config/ClientConfigFactory.php), lines 147–159,
returns null when TLS configuration is malformed or the client certificate path
is absent/empty. [NativeStreamTransport.php](../../src/Connection/NativeStreamTransport.php)
then selects `tcp://` for null TLS configuration.

Reproduced inputs include `tls => ['verifyPeer' => true]`,
`tls => ['clientCertificatePath' => '']`, and `tls => 'invalid'`. All are accepted
as plaintext configuration. A certificate key typo can therefore disable
encryption instead of failing validation. A plaintext peer could receive login
credentials; credential disclosure was not attempted in this audit.

Reject malformed supplied TLS configuration and make plaintext operation an
explicit choice for local tests. The README's main example also omits TLS despite
targeting RNIDS. TLS boolean options are cast rather than validated, adding
configuration surprises such as the string `'false'` becoming true.

### 3. P1 — Domain nameserver updates are missing and can be silently dropped

Issue: `rnids-epp-client-c8y`.

[DomainService.php](../../src/Domain/DomainService.php), lines 511–523, extracts
only contacts/statuses. [DomainUpdateSection.php](../../src/Domain/Dto/DomainUpdateSection.php)
has no nameserver representation.

```php
$client->domain()->update([
    'name' => 'example.rs',
    'add' => ['nameservers' => [['name' => 'ns2.example.rs']]],
]);
```

This throws. Adding a valid contact change to the same section makes the request
accepted, but the emitted XML contains only that contact change. Nameservers are
silently ignored. The legacy `eppUpdateDomainRequest` supports nameserver updates.
Add typed nameserver/glue changes and reject unsupported request fields instead
of losing them.

### 4. P1 — Contact update can target a different identifier than info/delete

Issue: `rnids-epp-client-m8o`.

[ContactIdPolicy.php](../../src/Contact/ContactIdPolicy.php), line 40, adds `OBL-`
to existing identifiers on update. Info and delete preserve the supplied ID.
Generated XML confirms:

```text
info('LEGACY-42')                       targets LEGACY-42
update(['id'=>'LEGACY-42', ...])         targets OBL-LEGACY-42
```

Non-prefixed existing contacts cannot be updated through the fluent API; if both
identifiers exist and are accessible, a different contact receives the update.
This is an explicitly documented and tested policy, but a dangerous API contract.
Identifier generation belongs to creation; updates should preserve registry IDs.

### 5. P2 — RNIDS extension-only domain updates are rejected

Issue: `rnids-epp-client-ajk`.

[DomainService.php](../../src/Domain/DomainService.php), line 490, parses the
extension but excludes it from the condition requiring a change. This rejects:

```php
$client->domain()->update([
    'name' => 'example.rs',
    'extension' => ['isWhoisPrivacy' => true],
]);
```

Changing privacy, operation mode, notification settings, or a remark requires
an unrelated base-domain change. Allow meaningful extension-only updates, which
the checked-in RNIDS protocol reference describes.

### 6. P2 — Contact update cannot express clearing fields consistently

Issue: `rnids-epp-client-odl`.

[ContactRequestFactory.php](../../src/Contact/ContactRequestFactory.php), lines
378–389, treats null as omission and rejects empty strings. Consequently neither
`fax => null` nor `fax => ''` clears an existing fax. With no other fields, both
requests throw; with other changes, null silently leaves fax unchanged. The same
normalizer affects other optional contact strings. The checked-in contact
protocol reference describes empty elements for clearing optional values.

There is a related create/update contradiction at lines 192–195: creating a legal
entity permits a blank person name when organization and `isLegalEntity='1'` are
present, but updating the same entity's postal address requires a nonempty name.
Both behaviors were reproduced. Model leave/set/clear distinctly and apply
compatible legal-entity rules across creation and updates.

### 7. P2 — Contact disclosure XML lacks required attributes

Issue: `rnids-epp-client-1ik`.

[ContactCreateRequestBuilder.php](../../src/Xml/Contact/ContactCreateRequestBuilder.php),
line 75, and
[ContactUpdateRequestBuilder.php](../../src/Xml/Contact/ContactUpdateRequestBuilder.php),
line 112, emit:

```xml
<contact:disclose flag="1">
  <contact:name/><contact:org/><contact:addr/>
  <contact:voice/><contact:email/>
</contact:disclose>
```

The first three children require `type="loc|int"` under
[RFC 5733's contact schema](https://www.rfc-editor.org/rfc/rfc5733.html#section-4).
The legacy enabled-disclosure builder supplies those attributes. The generated
XML was inspected locally; actual RNIDS rejection was not verified. Existing
tests check the disclosure opening tag and miss the invalid children.

### 8. P2 — Domain info drops subordinate hosts and an RNIDS privacy date

Issue: `rnids-epp-client-39n`.

[DomainInfoResponseParser.php](../../src/Xml/Domain/DomainInfoResponseParser.php),
line 218, reads only `domain:ns/*`. Sibling `domain:host` entries disappear, even
when the public API explicitly requests `info('example.rs', 'sub')`. A response
containing two subordinate hosts maps to no hosts field and empty nameservers.
Legacy `getDomainHosts()` preserved them. The current sub-mode unit test supplies
a delegated-nameserver fixture instead of subordinate hosts.

The documented RNIDS `whoisPrivacyPaidUntil` date is also absent from the
DTO/parser/mapper. Preserve these distinct response fields and use representative
fixtures.

### 9. P2 — Fatal stream errors and explicit logout leave stale lifecycle state

Issue: `rnids-epp-client-bhf`.

[NativeStreamTransport.php](../../src/Connection/NativeStreamTransport.php), lines
135–190, throws on invalid frames, oversized frames, EOF, and timeouts without
disconnecting. An oversized-prefix reproduction confirms the stream remains
open even though the error says `Closing connection`. After a partial read,
already-consumed bytes are lost, so continuing can misinterpret payload bytes as
the next length prefix.

Additionally, `session()->logout()` does not update `Client::$loggedIn` or
`Client::$initialized`. Reading the lifecycle paths shows that subsequent
`init()` returns early and `close()` attempts another logout. Centralize session
state and invalidate damaged transports. The oversize behavior was reproduced;
the explicit logout observation is based on control-flow inspection.

### 10. P2 — Runtime extension requirements are incomplete

Issue: `rnids-epp-client-sg2`.

[composer.json](../../composer.json), lines 5–8, declares PHP and `ext-json`, but
the core parser constructs `DOMDocument` and the RNIDS TLS transport needs
OpenSSL. Declare `ext-dom` and `ext-openssl` so production installation fails
early when required extensions are missing. PHPUnit's development requirements
can hide this packaging problem. Composer validation passing does not establish
that the declared runtime dependencies are complete.

### 11. P2 — Live testing can report green without exercising RNIDS

Issue: `rnids-epp-client-7op`.

[IntegrationConfig.php](../../tests/Integration/Support/IntegrationConfig.php),
lines 30–36, prioritizes the dummy PEM. Preflight checks readability, not whether
the certificate parses, matches its key, or has expired. Lines 99–137 turn
unreachable infrastructure into skips; explicitly running the full live suite
returned exit 0 with zero assertions during this audit.

Provide a strict mode for requested/CI live runs, remove dummy credentials from
live discovery, and surface certificate failures separately from networking.
The real certificate needs renewal and test endpoint connectivity needs restoring.

The integration suite contains only eight tests. It lacks live host lifecycles,
domain renewal/deletion/transfer, and poll acknowledgment. The domain registration
test creates a unique domain without deleting it afterward. Cleanup failures in
contact/reassignment tests are suppressed. Extend meaningful flow coverage and
make test-created state and failed cleanup visible. The aggregate 90.51% line
coverage gate does not compensate for these omissions.

### 12. P2 — The exposed DNSSEC flag cannot provision DNSSEC

Issue: `rnids-epp-client-69g`.

[DomainExtensionXmlBuilder.php](../../src/Xml/Domain/DomainExtensionXmlBuilder.php),
line 27, emits the RNIDS `dnsSec` boolean, but no secDNS namespace, DS-record DTO,
builder, or parser exists in `src`. The checked-in
[RNIDS domain protocol reference](../epp-protocol/epp-domain-commands.md) says the
flag is ignored and DNSSEC state derives from secDNS data. The public
`dnsSec => true` registration example therefore cannot enable DNSSEC on its own.

Document that limitation and implement actual typed secDNS record handling if
DNSSEC provisioning is intended. This is a capability/documentation gap supported
by the local RNIDS reference, not an observed live rejection.

## API and maintenance concerns

These are design judgments in addition to the concrete defects above. Existing
issue `rnids-epp-client-szv` tracks public API consistency and documentation drift;
its notes were updated with this audit.

- **The strongly typed public API is mostly internal.** Fluent services take and
  return arrays while internal DTOs are immediately unpacked again. Callers lose
  much of the discoverability and static guarantees promised by the architecture.
  Many shapes still use `mixed`, and unknown fields can disappear silently.
- **Business policy is hardcoded into protocol operations.** Besides `OBL-` IDs,
  contact extension updates overwrite `identDescription` with vendor promotional
  text. These are documented choices, but registry behavior and one registrar's
  policy should be separated.
- **Similar operations have different shapes.** Domain/host changes use nested
  add/remove sections; contact changes use `addStatuses`/`removeStatuses`.
  Contact info exposes flat postal aliases while update expects nested input.
- **Transfer naming hides operation semantics.** The fluent methods expose
  request/query/approve as `getCode()`/`getState()`/`transfer()`. The DTO also
  defines cancel/reject, but those operations have no public service path.
- **Transport substitution is awkward at the main entry point.** `Transport` is
  an interface, but `Client` constructs its factory/services internally and
  accepts no injected transport. Client lifecycle tests resort to reflection.
- **Response context is detached from results.** Callers retrieve metadata from
  mutable `responseMeta()` after the operation. It means "last parsed response",
  so a transport or parsing failure can leave metadata from an earlier command.
- **Transaction IDs repeat across client instances.** Each service starts its own
  deterministic counter. Even independently of response validation, tracing
  transactions across processes or reconnecting workers becomes ambiguous.

Fix findings 1–4 before treating the client as operationally dependable. Then
address update/data-loss semantics and make the live suite fail visibly when an
explicit validation run cannot exercise the registry. The remaining API cleanup
is easier to assess once those behavior contracts are reliable.
