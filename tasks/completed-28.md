# Task 28: Update Contact API Documentation for New Contract

## Objective
Align all contact-facing docs with enforced runtime behavior for ID normalization/generation and extension comment policy.

## Implementation Plan
- [x] Update `docs/api-contact.md`.
- [x] Update `docs/api-reference.md` with cross-service behavior notes.
- [x] Update `README.md` with concise policy note for contact flows.
- [x] Ensure examples do not contradict new policy.

## Acceptance Criteria
- Documentation precisely matches actual request behavior.
- Consumers can understand ID policy without reading source code.
- No outdated examples remain for contact create/update.

## Verification Commands
- `vendor/bin/phpcs docs README.md`
- `rg -n "contact|OBL-|identDescription|Object Creation provided by Oblak Solutions" docs README.md`

## Dependencies / Notes
- Requires implementation completion of Tasks 22–27.
- Wording kept explicit about library-enforced behavior.

## Outcome
Documentation now explicitly describes the enforced contact policy and includes integration notes for live reassignment target contact resolution.

Key updates:
- `docs/api-contact.md`
- `docs/api-reference.md`
- `README.md`
- `docs/api-domain.md` (added `update()` section for consistency with implemented API)

Verification evidence:
- `vendor/bin/phpcs docs README.md` ✅
- `rg -n "contact|OBL-|identDescription|Object Creation provided by Oblak Solutions" docs README.md` ✅
