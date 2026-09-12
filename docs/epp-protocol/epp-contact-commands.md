# Contact Commands

Reference for `contact:*` operations in RsReg2.

## `contact:check`

### Purpose
Check whether one or more contact identifiers are available.

### Request
- `<check><contact:check>` with one or more `<contact:id>` values.
- Identifier may be ExternalId or Roid (ExternalId wins if both match different records).

### Response (`contact:chkData`)
- Repeating `<cd>` entries containing:
  - `<id avail="1|0|true|false">` (XML boolean lexical forms)
  - optional `<reason>`

### Typical Errors
- `2001` syntax
- `2003` missing `contact:id`

## `contact:create`

### Purpose
Create new contact object.

### Core Request Fields
- `contact:id` (desired ExternalId)
- `contact:postalInfo` (`type="int|loc"`) with:
  - `name`
  - optional `org`
  - `addr` (`street`, `city`, optional `sp`, `pc`, `cc`)
- `voice` (required by the client based on observed registry behavior)
- optional `fax`
- `email`

### RNIDS Extension Fields (`contact-ext`)
- `ident`
- `identDescription`
- `identExpiry`
- `isLegalEntity`
- `identKind` (`personal_ID|personal_IDDocument|passport|other`)
- `vatNo`

### Response (`contact:creData`)
- `id`, `crDate`

### Typical Errors
- `2302` contact already exists
- `2400` when voice was omitted in September 12, 2026 development-registry tests;
  an otherwise identical request with voice succeeded. The client rejects missing,
  null, or empty voice locally before sending a create command.

## `contact:info`

### Purpose
Retrieve full contact data.

### Request
- `<info><contact:info><contact:id>...</contact:id></contact:info></info>`
- ID lookup supports ExternalId or Roid (ExternalId precedence).

### Response (`contact:infData`)
Includes identity, status, postal info, communication fields, sponsor/create/update/transfer metadata.

### Extension Response
- RNIDS `contact-ext` returns identification fields (`ident`, `identKind`, `identExpiry`, `isLegalEntity`, ...).

### Typical Errors
- `2303` contact does not exist

## `contact:update`

### Purpose
Update contact attributes.

### Request Structure
- `contact:id`
- optional `contact:add`/`contact:rem` for statuses
- optional `contact:chg` for modified data (`postalInfo`, `voice`, `fax`, `email`, ...)
- optional RNIDS `contact-ext` with changed extension fields

### Important Behavior Notes
- Updated contact data affects all linked domain usages.
- If one field in `addr` changes, all `addr` fields should be supplied.
- Empty fax, identification, identification-description, and VAT-number elements
  clear those values in the development registry.
- Empty voice is rejected with `2003` and reason `Phone is mandatory field`.
- Empty organization, province, and postal-code elements returned `1000` in
  September 12, 2026 tests but retained the prior values. The public library rejects
  these known no-op clearing requests; use omission/null or a nonempty replacement.
- Empty authInfo was accepted, but contact info does not return it for readback.
- See the project's [registry compatibility record](../registry-compatibility.md).
- Contact `id` itself cannot be changed.

### Typical Errors
- `2303` contact does not exist
- `2305` association prohibits operation (ccTLD policy constraints)
- `2003` required parameter missing (including attempted voice clearing)

## `contact:delete`

### Purpose
Delete contact object.

### Required Field
- `contact:id`

### Response
- Success (`1000`/`1001`), no `resData`.

### Typical Errors
- `2303` contact does not exist
- `2305` contact is associated with other objects (for example domain links)
