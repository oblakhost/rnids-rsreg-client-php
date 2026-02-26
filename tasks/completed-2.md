# Task 2: Implement EPP result code enums and exception handling strategy

## Objective
Implement a typed EPP error handling model by introducing int-backed result code enums and mapping protocol failures to specific exceptions. This improves discoverability, consumer ergonomics, and consistency with RNIDS-specific behavior while keeping compatibility for unknown/forward result codes.

## Implementation Plan
- [x] Add an int-backed `EppResultCode` enum with known EPP/RNIDS-relevant codes and success classification.
- [x] Extend response metadata with helpers for known enum mapping and success checks.
- [x] Introduce protocol exception subclasses and a centralized `ProtocolExceptionFactory` for code-to-exception mapping.
- [x] Refactor result code policy to rely on metadata helpers and the exception factory.
- [x] Add PHPUnit unit tests for enum behavior, policy assertions, and factory mappings (including unknown code fallback).
- [x] Run PHPCS, PHPStan, and targeted PHPUnit tests; fix any issues.

## Acceptance Criteria
- [x] Known EPP result codes are represented as a typed int enum.
- [x] Non-success responses throw semantically specific protocol exceptions when mapping exists.
- [x] Unknown result codes still throw a safe generic protocol exception.
- [x] Success codes remain accepted (including poll/session-success variants).
- [x] New tests cover enum mapping, policy behavior, and exception factory behavior.

## Outcome
Implemented `EppResultCode` as an int-backed enum and added metadata helpers in `ResponseMetadata` for known-code mapping and success checks. Added a protocol exception mapping layer (`ProtocolExceptionFactory`) and specific failure classes for common EPP failure groups (authentication/authorization/object/policy paths), then wired `ResultCodePolicy` to throw mapped exceptions.

Added focused unit tests for enum resolution, policy behavior, and exception mapping fallback. Targeted PHPUnit and PHPStan pass. PHPCS still reports pre-existing repository violations in baseline files (`src/Client.php`, `src/Xml/Session/LoginRequestBuilder.php`, and existing `*Exception` base classes), outside the scope of this task.
