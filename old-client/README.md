# Archived implementation reference

`old-client/` is a frozen source reference for the maintained client in `src/`.
It is retained so maintainers can compare RNIDS command behavior and historical
XML. It is outside the 2.x support and LTS commitment.

Use the current [installation guide](../README.md), [API documentation](../docs/api-reference.md),
and [migration guide](../UPGRADING.md) for applications. The project is an
independent third-party integration, unaffiliated with RNIDS.

## Archive boundary

- Composer autoloads `RNIDS\` from `src/`; it does not load this directory.
- Composer distributions and the custom release ZIP exclude this directory.
- The supported runtime, PHP compatibility checks, and coverage gate apply to
  the maintained implementation. They do not establish that these examples work
  on supported PHP versions or against today's registry.
- The legacy autoloader loads example bootstrap code, and the examples contain
  registry commands. Read them as historical material; do not use them as the
  current setup or test procedure.
- Preserve the existing source and [legacy license](LICENSE.md). The root
  package's Apache-2.0 declaration does not replace this directory's license file.

## Maintenance policy

Fixes belong in `src/` with current regression tests. Compare behavior with this
archive when helpful, but keep the current fluent API, native stream transport,
and typed internal data model. There is no separate legacy release line.

The [behavior inventory](../docs/old-client-analysis.md) maps the old operations
to their maintained counterparts and distinguishes historical capabilities from
the current supported API. The original `README` remains as historical setup
notes; current certificate configuration is documented in the client API.
