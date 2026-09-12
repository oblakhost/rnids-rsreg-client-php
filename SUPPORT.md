# Support and compatibility

This is an independent third-party PHP library for integrating with RNIDS/RSreg.
It is not affiliated with, endorsed by, or supported by RNIDS. The Composer package
name and `RNIDS\` namespace identify the integration target and remain unchanged
for compatibility. Project support comes from this repository's maintainers.

## Maintained releases

| Release line | Status | Bug and security fixes |
| --- | --- | --- |
| Latest stable 2.x | Maintained; LTS candidate | Through September 30, 2028 |
| 1.x and earlier | Superseded | Upgrade to 2.x; no routine backports |
| Prereleases | Evaluation | Upgrade to the next prerelease or stable version |

Fixes are delivered in the latest 2.x release. Applications must install those
updates to receive them; individual older patch releases are not maintained in
parallel. Maintenance is provided on a best-effort basis by the project maintainers.
Use [UPGRADING.md](UPGRADING.md) when migrating from 1.x.

The 2.x line is **not yet designated LTS**. That designation requires recorded
RNIDS acceptance results for DNSSEC DS provisioning, all transfer stages using
two registrar accounts, approved poll acknowledgment, secure-mode changes, and
contact clearing. The [registry compatibility record](docs/registry-compatibility.md)
distinguishes completed checks from outstanding external workflows. Successful
offline tests or a skipped live test do not establish registry acceptance. This
is a project support policy, not RNIDS certification.

The [LTS acceptance procedure](docs/lts-acceptance.md) defines the remaining
operator prerequisites and the evidence required before changing this designation.

`master` publishes stable 2.x releases; `develop`, `alpha`, and `beta` publish
prereleases. Before development moves to a new major on `master`, create and test
a separate 2.x maintenance/release branch for the remainder of this support window.
No published version tags are rewritten.

## PHP and dependencies

The 2.x PHP compatibility floor remains **8.1**. CI requires PHP 8.1 through 8.5;
new stable PHP releases are added after compatibility verification. The required
extensions are DOM, OpenSSL, and JSON. Unicode domain and host input additionally
requires `intl`; ASCII Punycode input works without it.

For production, use an upstream-supported PHP release, currently preferably 8.4
or 8.5. PHP 8.1 reached upstream end of life on December 31, 2025. Keeping library
compatibility with an older runtime does not provide security fixes for PHP,
OpenSSL, or the operating system. See the PHP project's
[supported versions](https://www.php.net/supported-versions.php) and
[end-of-life dates](https://www.php.net/eol.php).

## Public API contract

The fluent API, documented request/response arrays, transport interface, and public
PHP signatures remain compatible throughout 2.x. Existing aliases remain available.
New implementation helpers explicitly marked `@internal` are outside that contract.

- Preserve existing parameter names, accepted documented types, result keys, and
  their meanings. Optional parameters, methods, and result fields may be added;
  consumers should read the fields they need rather than compare complete arrays.
- `check()` returns a direct list. Dates documented as `DateTimeImmutable` remain
  objects; nullable fields remain nullable. Mutations documented to return `[]`
  continue to do so, with result metadata available through `responseMeta()`.
- For supported optional contact text updates, omission or `null` leaves the field
  unchanged and `''` clears it. Empty voice, organization, province, and postal-code
  updates are rejected locally because the registry rejects or ignores them.
  Required or typed fields follow the operation's validation rules. Empty authInfo
  can be submitted but its resulting state is not exposed for readback. See the
  [contact API](docs/api-contact.md).
- Contact creation requires a nonempty `voice` phone number and retains the
  `OBL-` prefix policy. Pass the returned ID to later
  operations, which preserve it literally. The prefix is library policy, not an
  RNIDS affiliation marker or a general registry requirement.
- Protocol failures preserve result codes and metadata through `ProtocolException`;
  transport and malformed-response failures use their documented exception types.
  Registry messages and diagnostic exception text are not stable identifiers.
- Security and protocol correctness fixes may reject malformed or unsafe input
  that an earlier bug accepted. Such changes must be explained in release notes.
  Documented successful behavior is covered by regression tests.

Breaking API changes require a new major version under
[Semantic Versioning](https://semver.org/). Deprecations are announced in a minor
release with a migration path and are retained for the rest of 2.x. The PHP floor
will not be raised in a 2.x patch or minor release.

## Operation outcomes

An accepted EPP command does not always mean the requested registry state is
already final. Inspect `responseMeta()` immediately after the operation and use
object information or the relevant poll/transfer response to confirm completion.
In the test registry, a domain deletion can return `1000` while the object remains
`pendingDelete`; linked contacts must remain until the domain is actually removed.

If a connection fails after sending a mutation, the registry may have applied it
without delivering its response. Reconnecting does not resolve that uncertainty.
The client does not automatically replay commands. Check the current object state
and account for pending work before deciding whether to retry a registration,
renewal, transfer, update, or deletion. Save response metadata before sending another
command. Transport failures, malformed responses, and transaction mismatches clear
metadata to prevent reuse of stale results. A valid EPP rejection retains its own
result code and transaction IDs, including when it raises `ProtocolException`.

## Getting help

Report library defects in this repository's
[issue tracker](https://github.com/oblakhost/rnids-rsreg-client-php/issues), including
the library/PHP versions and a minimal reproduction with synthetic data. Use
[SECURITY.md](SECURITY.md) for confidential vulnerability reports. Registry accounts,
certificates, policy decisions, and registry availability are managed through your
registrar's authorized RNIDS channels.
