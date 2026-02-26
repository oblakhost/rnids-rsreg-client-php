# XML Handling Rules

The client must produce valid EPP XML and parse responses predictably.

## XML Construction

- Keep request XML generation inside `src/Xml/` (builders/encoders), not in operation services.
- Prefer typed DTOs as input for XML builders.
- Keep XML namespace declarations explicit and stable.
- Always include a client transaction id (`clTRID`) on commands.
- Keep generated XML deterministic (same input => same XML structure).

## Namespaces

At minimum, support and register these namespaces explicitly:

- EPP: `urn:ietf:params:xml:ns:epp-1.0`
- Domain: `urn:ietf:params:xml:ns:domain-1.0`
- Contact: `urn:ietf:params:xml:ns:contact-1.0`
- Host: `urn:ietf:params:xml:ns:host-1.0`
- RNIDS extension: `http://www.rnids.rs/rnids-epp/rnids-1.0`

## XML Parsing

- Parse response XML via namespace-safe XPath queries.
- Keep parsing logic inside dedicated parser/response mapper classes.
- Convert XML to typed response objects; avoid leaking raw DOM into public API.
- Validate result codes and convert protocol/transport errors into exceptions.

## Templates

- If template files are used (`templates/`), keep placeholders minimal and type-safe.
- Prefer programmatic XML builders for complex requests over fragile string concatenation.
- Template changes must be covered by tests for request generation.
