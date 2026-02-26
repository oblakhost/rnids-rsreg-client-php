# Old Client Reference

The `old-client/` directory is the functional reference for behavior parity while building the new implementation in `src/`.

## What It Contains

- EPP transport and framing implementation
- Request/response XML classes for core EPP objects
- RNIDS registry-specific connection and extension classes
- CLI examples for all common operations

## Migration Intent

- Preserve behavior coverage from `old-client/`
- Replace mutable DOM-heavy public API with typed DTOs and fluent services
- Keep RNIDS fields and workflows explicit in the new API

## Old → New Responsibility Mapping

- `old-client/Protocols/EPP/eppConnection.php` -> `src/Connection/*`
- `old-client/Protocols/EPP/eppRequests/*` -> `src/Xml/*` + operation request DTOs
- `old-client/Protocols/EPP/eppResponses/*` -> `src/Xml/*` + typed response DTOs
- `old-client/Protocols/EPP/eppData/*` -> `src/Domain|Contact|Host/*` DTO/value objects
- `old-client/Protocols/EPP/eppExtensions/rnids-1.0/*` -> `src/Extension/Rnids/*`
- `old-client/Examples/*` -> integration/usage tests and docs examples

## Functional Coverage To Preserve

- Session: hello/login/logout/poll
- Domain: check/info/register/update/renew/delete/transfer flows
- Contact: create/info/update/delete
- Host: check/info/create/update/delete
- RNIDS extension data for contact/domain operations
