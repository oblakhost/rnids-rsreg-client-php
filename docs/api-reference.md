# API Reference

Client-oriented API documentation for the RNIDS/RSreg EPP library.

## Entry Points

- [Client](./api-client.md)
- [Session Service](./api-session.md)
- [Domain Service](./api-domain.md)
- [Contact Service](./api-contact.md)
- [Host Service](./api-host.md)

## API Shape Notes

- Domain, contact, and host `check()` methods return a **direct list of items**.
- They do not wrap that list in an `items` key.

## Protocol Documentation

Raw EPP protocol behavior and RNIDS specifics are documented separately:

- [EPP Protocol Reference Index](./epp-protocol/epp-reference-index.md)
