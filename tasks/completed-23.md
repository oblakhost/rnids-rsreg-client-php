# Task 23: Enforce RNIDS Contact Extension Comment Text

## Objective
Guarantee a consistent RNIDS extension comment value for contact create/update operations regardless of caller input.

## Implementation Plan
- [x] Introduce a single constant for required comment text:
  - `Object Creation provided by Oblak Solutions.`
- [x] Update contact create normalization so `extension.identDescription` is always set to the constant.
- [x] Update contact update normalization so `extension.identDescription` is always set to the constant.
- [x] Ignore caller-provided `identDescription` values.
- [x] Ensure extension serialization still omits empty extension blocks when no extension fields are present except enforced comment behavior.

## Acceptance Criteria
- Outgoing create XML always includes `contactExt:identDescription` with required exact text when extension block is emitted.
- Outgoing update XML always includes `contactExt:identDescription` with required exact text when extension block is emitted.
- Caller-provided comment overrides no longer alter outbound payload.

## Verification Commands
- `vendor/bin/phpunit tests/Unit/Contact tests/Unit/Xml/Contact`
- `vendor/bin/phpcs src/Contact src/Xml/Contact tests/Unit/Contact tests/Unit/Xml/Contact`

## Dependencies / Notes
- Depends on Task 22 wiring location decisions.
- Keep punctuation and spacing exactly as specified.

## Outcome
Implemented centralized enforcement of RNIDS contact extension comment text in `ContactRequestFactory` using `ENFORCED_IDENT_DESCRIPTION` with exact value `Object Creation provided by Oblak Solutions.`.

Both create and update normalization paths now force `extension.identDescription` to the required text, ignoring any caller-provided override. Existing extension handling remains intact and continues to emit extension blocks only when extension data is present.
