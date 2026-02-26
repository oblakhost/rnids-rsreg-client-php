# Testing Rules

Testing must validate behavior clearly and keep the suite maintainable.

## Test Stack

- Use **PHPUnit** for unit and integration tests.
- Store default PHPUnit configuration in `phpunit.xml.dist` and bootstrap in `tests/bootstrap.php`.
- Plan for **Codecov** integration and meaningful coverage reporting.

## Coverage Philosophy

- Cover all supported client functionalities (session, domain, contact, host, RNIDS extensions, XML build/parse, transport framing).
- Prioritize high-value scenarios and critical flows over excessive micro-tests.
- Do not chase artificial "1000%" test volume; focus on confidence and maintainability.

## Test Design Rules

- Prefer positive, behavior-focused tests that verify what the client should do.
- Avoid negative/absence tests with low value (for example: asserting something was removed and is "not there" without validating user-visible behavior).
- Keep tests logical, deterministic, and readable.
- Use explicit, domain-relevant fixtures and names.
- One test should validate one behavior or flow.

## Required Test Areas

- XML request generation correctness (including namespaces and `clTRID`).
- XML response parsing into typed DTOs.
- Result-code handling and exception mapping for protocol/transport errors.
- EPP transport/frame behavior:
  - 4-byte length prefix encode/decode in `EppFrameCodec`
  - Native stream transport timeout and EOF error mapping
  - TLS stream context option mapping from typed config objects
- Fluent API operation coverage:
  - Session: hello/login/logout/poll
  - Domain: check/info/register/update/renew/delete/transfer flows
  - Contact: create/info/update/delete
  - Host: check/info/create/update/delete
- RNIDS-specific first-class fields (contact/domain extension behavior).
