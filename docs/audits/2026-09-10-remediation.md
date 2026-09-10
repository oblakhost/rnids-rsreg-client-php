# Audit remediation — 2026-09-10

The locally reproducible defects from the [library audit](2026-09-10-library-review.md)
have been repaired and covered by regression tests. No RNIDS connections were made
during this remediation: EPP access is not yet available. Registry acceptance remains
unverified and is tracked separately as `rnids-epp-client-0aa`.

## Changes

- **Session ordering and correlation:** initialization consumes the unsolicited
  greeting before sending login and waits for the actual login result. Commands
  require matching response transaction IDs. All operation groups share session
  state and a transaction generator with a random per-client prefix. Explicit
  logout and fatal I/O or protocol failures invalidate the session; initialization
  can then reconnect. Failed exchanges and reconnects clear stale metadata.
- **Transport and TLS:** malformed TLS options fail validation. Native plaintext
  requires explicit opt-in. Transport failures use the transport exception type
  and close damaged streams, including partial reads, timeouts, EOF, and oversized
  frames. The client constructor and `ready()` now accept a `Transport` for testing
  or alternate implementations.
- **Domain mutations:** nameserver add/remove supports glue addresses, and unknown
  update fields fail instead of silently disappearing. RNIDS extension-only updates
  work. Registrant and DNSSEC changes must each be sent separately from other
  mutations because RNIDS gives them precedence over other update fields. Both
  registration and update validate operation mode as `normal` or `secure`.
- **Domain information and DNSSEC:** info preserves subordinate hosts and
  `whoisPrivacyPaidUntil`. Typed secDNS 1.1 DS records support registration, update
  add/remove/remove-all, and info parsing. Default login advertises secDNS only if
  the greeting supports it; explicit extension URI configuration remains authoritative.
  The API exposes all five transfer operations through explicit names while keeping
  the previous aliases.
- **Contact behavior:** update preserves the supplied registry ID. Optional string
  fields support explicit clearing, legal-entity postal updates follow the create
  rule, and identification descriptions are no longer replaced with vendor text.
  Disclosure XML includes postal type attributes, and response parsing accepts both
  numeric and lexical XML boolean values.
- **API contracts and packaging:** service PHPDocs describe known request and
  response structures precisely. API documentation now matches signatures, enum
  values, returned postal fields, and new operations. Composer declares the DOM and
  OpenSSL runtime extensions.
- **Live test infrastructure:** local certificate/key/expiry/CA checks precede
  network probing. Dummy credentials are excluded from discovery, TLS verification
  is enabled, and explicitly requested live runs fail when tests skip. Six broader
  flows include owned contact/domain/host lifecycles, renewal, deletion, and an
  explicitly selected poll acknowledgment. Cleanup failures remain visible. CI
  live runs require opt-in and do not cancel an active run on a later push.

## Compatibility notes

- Native connections must configure `tls.clientCertificatePath`. Use
  `allowPlaintext => true` only when intentionally connecting to a plaintext peer.
  Injected transports control their own connection security. TLS booleans must be
  actual booleans rather than strings such as `'false'`.
- Contact creation retains the existing `OBL-` ID policy. Pass the ID returned by
  `create()` to subsequent operations: `update()` now sends it literally, just as
  `info()` and `delete()` do.
- Contact updates use omission or `null` for "leave unchanged" and `''` for clearing
  supported optional strings. Required values and typed extension fields cannot
  be cleared with empty strings. See [Contact API](../api-contact.md).
- Callers that relied on automatic identification descriptions must now provide
  `extension.identDescription` themselves.
- Domain updates reject unsupported keys and mixed registrant/DNSSEC mutations.
  Split those changes into separate requests. The RNIDS `dnsSec` flag alone still
  cannot provision DNSSEC: provide `dnssec.records` during registration or
  `dnssec.add`/`remove`/`removeAll` during update. This implementation supports DS
  records; secDNS `keyData`, `urgent`, and `maxSigLife` are outside its scope.
  See [Domain API](../api-domain.md).
- Public services retain their array request/response API for compatibility, with
  typed DTOs internally and precise PHPDocs. Existing contact status field names
  and flat postal response aliases remain supported.
- `composer test:live` now returns a nonzero status when required access or fixtures
  are unavailable. Routine local verification remains `composer test`.

## Verification

| Check | Result |
| --- | --- |
| `composer test`, PHP 8.2.31 | Passed: **361 tests, 1,022 assertions**; PHPStan and PHPCS passed |
| `composer test:coverage`, PHP 8.1.34 with PCOV | Passed: **92.33%** line coverage, 3,179/3,443 lines, above the 90% gate |
| Real native transport against a local TCP EPP peer | Passed: greeting/login/poll/logout ordering, rejected login, fragmented 73 KB frame, truncated EOF, timeout |
| Explicit live run with credentials removed | Exit **1**: six tests skipped, zero assertions; rejected before RNIDS network probing |
| `composer validate --strict` | Passed |
| `composer audit` | No vulnerability advisories found |
| Workflow YAML parsing and `git diff --check` | Passed |

The network sandbox blocks local sockets; the full local gate and coverage run
therefore ran with permission to bind loopback sockets. No RNIDS credentials or
registry access were needed for these successful checks.

Composer's audit identified three development-tool advisories. The lockfile now
uses PHPCSUtils 1.2.3, PHP_CodeSniffer 3.13.6, and WordPress Coding Standards 3.4.1,
plus compatible supporting dependency updates. The upstream notices are
[PHPCSUtils GHSA-r6hr-vr92-vv28](https://github.com/PHPCSStandards/PHPCSUtils/security/advisories/GHSA-r6hr-vr92-vv28),
[PHP_CodeSniffer GHSA-hmqg-cxww-wqhq](https://github.com/PHPCSStandards/PHP_CodeSniffer/security/advisories/GHSA-hmqg-cxww-wqhq),
and [WPCS GHSA-3pwp-g2mj-5p3v](https://github.com/WordPress/WordPress-Coding-Standards/security/advisories/GHSA-3pwp-g2mj-5p3v).

## RNIDS validation still needed

Run the live suite once a test account, valid matching client certificate/private
key, CA configuration, required fixtures, and test credit are available. The prior
audit found the existing certificate expired; credentials have not been renewed
or validated here. Follow [Contributing](../../CONTRIBUTING.md) for setup.

In addition to the automated live lifecycles, confirm DS provisioning, deferred
secure-mode changes, and contact clearing against RNIDS. Full transfer workflows
need two registrar accounts and an authorized transferable test domain. A specific
queue message must be selected before testing poll acknowledgment. Offline XML
and stream tests establish implementation behavior, but cannot establish registry
acceptance of these operations.
