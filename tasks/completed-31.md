# Task 31: Domain Extension DTO Naming and XML Builder Deduplication

## Objective
Improve maintainability and readability of domain extension handling by aligning DTO naming with cross-command usage and removing duplicate extension XML helper logic between domain register and update builders.

## Implementation Plan
- [x] Rename `DomainRegisterExtension` to a command-agnostic name (for example `DomainExtension`) and update all references.
- [x] Keep constructor and field semantics stable to avoid API/behavior regressions.
- [x] Extract shared domain extension XML composition logic used by register/update builders into a shared helper/trait/class.
- [x] Update and/or add unit tests to confirm register and update XML output remains deterministic and unchanged.
- [x] Run focused domain/unit suites and static checks impacted by the refactor.

## Acceptance Criteria
- Domain register and update paths continue to emit valid and equivalent extension XML.
- Code duplication between register/update extension XML helpers is removed.
- All affected tests and checks pass.

## Outcome
Completed. Renamed the extension DTO to `DomainExtension` and updated all domain register/update references so constructor fields and behavior stayed unchanged. Extracted shared RNIDS extension XML generation into `src/Xml/Domain/DomainExtensionXmlBuilder.php` and wired both register and update builders through it, removing duplicated extension helper logic.

Updated domain builder tests to use the new DTO name and confirmed deterministic XML output remains intact. Verified with focused checks: PHPUnit (`DomainRegisterRequestBuilderTest`, `DomainUpdateRequestBuilderTest`) plus PHPCS and PHPStan on impacted files.
