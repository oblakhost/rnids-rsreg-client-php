# API Reference

Client-oriented API documentation for an independent third-party RNIDS/RSreg EPP
library. This project is not affiliated with, endorsed by, or supported by RNIDS.
The [support policy](../SUPPORT.md) defines the 2.x compatibility contract; see the
[upgrade guide](../UPGRADING.md) when migrating from 1.x.

## Entry Points

- [Client](./api-client.md)
- [Session Service](./api-session.md)
- [Domain Service](./api-domain.md)
- [Contact Service](./api-contact.md)
- [Host Service](./api-host.md)

## API Shape Notes

- Domain, contact, and host `check()` methods return a **direct list of items**.
- They do not wrap that list in an `items` key.
- Contact creation retains `OBL-` ID generation. Update/info/delete preserve existing registry IDs.
- Contact extension values preserve caller input. An omitted or null optional update field is
  unchanged; an empty string clears a supported optional text field.
- Services retain their array API with explicit PHPDoc shapes and typed internal DTOs.
- Domain transfer methods have explicit request/query/approve/cancel/reject names; legacy aliases remain.

## Live Integration Notes

- Offline tests exercise framing and session behavior against a local EPP peer.
- Live lifecycle tests create and clean up their own objects. Poll acknowledgment requires
  an explicitly configured message ID. Full setup is in [Contributing](../CONTRIBUTING.md).
- Explicit live test runs return a failure when setup is unavailable or tests are skipped.

## Protocol Documentation

Raw EPP protocol behavior and RNIDS specifics are documented separately:

- [EPP Protocol Reference Index](./epp-protocol/epp-reference-index.md)
