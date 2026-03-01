# Task 31: Domain Extension DTO Naming and XML Builder Deduplication

## Objective
Improve maintainability and readability of domain extension handling by aligning DTO naming with cross-command usage and removing duplicate extension XML helper logic between domain register and update builders.

## Implementation Plan
- [ ] Rename `DomainRegisterExtension` to a command-agnostic name (for example `DomainExtension`) and update all references.
- [ ] Keep constructor and field semantics stable to avoid API/behavior regressions.
- [ ] Extract shared domain extension XML composition logic used by register/update builders into a shared helper/trait/class.
- [ ] Update and/or add unit tests to confirm register and update XML output remains deterministic and unchanged.
- [ ] Run focused domain/unit suites and static checks impacted by the refactor.

## Acceptance Criteria
- Domain register and update paths continue to emit valid and equivalent extension XML.
- Code duplication between register/update extension XML helpers is removed.
- All affected tests and checks pass.

## Outcome
(Filled in on completion — what was done, any deviations from the plan, follow-up notes.)
