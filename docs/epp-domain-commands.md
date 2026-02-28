# Domain Commands

Reference for `domain:*` operations in RsReg2.

## `domain:check`

### Purpose
Check availability for one or more domain names.

### Request
- `<check><domain:check>` with one or more `<domain:name>`.

### Response (`domain:chkData`)
- Repeating `<cd>` entries with:
  - `<name avail="1|0">`
  - optional `<reason lang="en|sr-Latn-RS">`

### Typical Errors
- `2001` syntax
- `2005` invalid domain syntax

## `domain:create`

### Purpose
Register a domain.

### Core Request Fields
- `domain:name`
- optional `domain:period` (`unit="y|m"`)
- optional nameservers via `domain:ns`
- `domain:registrant`
- required contacts: `domain:contact type="admin"` and `type="tech"`

Identifiers for registrant/contact can be ExternalId or Roid; ExternalId takes precedence if both match.

### Extension Fields

RNIDS domain extension (`domain-ext`) may include:
- `remark`
- `isWhoisPrivacy`
- `operationMode` (`normal|secure`)
- `notifyAdmin`
- `dnsSec` (documented as ignored and derived from secDNS data)

Optional `secDNS:create` supports DS data:
- `keyTag`
- `alg` (allowed: `3,5,6,7,8,10,13,14`)
- `digestType` (allowed: `1,2,3,4`)
- `digest`

### Response (`domain:creData`)
- `name`, `crDate`, `exDate`

### Typical Errors
- `2003` missing required contacts or DNSSEC required fields
- `2005` invalid domain / invalid DNSSEC values
- `2102` unsupported DNSSEC option (`maxSigLife`)
- `2302` domain exists
- `2303` contact not found
- `2306` policy errors (duplicate NS, unsupported keyData)

### Client Convenience API (this library)

- Full payload (legacy-compatible):
  - `domain()->register(array $request)`
- Simplified fluent form:
  - `domain()->register($domain, $registrant, $adminContact, $techContact, $nameservers, $years = 1, $authInfo = null, $extension = null)`
- In simplified form, nameservers are required and may be provided as:
  - single hostname string,
  - list of hostname strings,
  - or detailed list with `name` + optional `addresses`.

## `domain:info`

### Purpose
Retrieve details of a domain.

### Request
- `<info><domain:info><domain:name>...</domain:name></domain:info></info>`

### Response (`domain:infData`)
Includes: `name`, `roid`, `status`, `registrant`, contacts, nameservers, `clID`, timestamps (`crDate`, `exDate`, etc.).

### Extension Response
- RNIDS domain extension includes operational/privacy/verification fields (`isWhoisPrivacy`, `operationMode`, `isDomainVerified`, `whoisPrivacyPaidUntil`, ...).
- Optional secDNS `<infData>` includes DS records.

### Typical Errors
- `2005` invalid domain syntax
- `2201` authorization error
- `2303` domain not found

## `domain:update`

### Purpose
Modify domain details, nameservers, contacts, locks, registrant, RNIDS extension values, or DNSSEC records.

### Standard Request Structure
- `domain:name`
- optional `domain:add`, `domain:rem`, `domain:chg`
- optional extension blocks (`domain-ext`, `secDNS:update`)

### RsReg2-specific Processing Rules
1. If `domain:chg/domain:registrant` is present, registrant-change flow is triggered and other changes are ignored.
2. If `secDNS:update` is present, non-DNSSEC changes are ignored.
3. If `domain:rem/domain:status` is present, unlock flow is triggered; only `ClientUpdateProhibited` can be changed via EPP.
4. In secure mode, changing DNS/admin contact/privacy or switching `secure -> normal` creates a pending change request.
5. Otherwise, updates are committed immediately.

### DNSSEC Update Notes
- Supports `secDNS:rem` (`all` or specific `dsData`) and `secDNS:add` (`dsData`).
- `keyData` and some optional attributes/options are not supported.

### Typical Errors
- `2003` missing required fields (including DNSSEC required fields)
- `2005` invalid domain/name server/DNSSEC values
- `2102` unsupported options (`maxSigLife`, `urgent`)
- `2201` authorization error
- `2303` domain/contact/DS not found
- `2304` status prohibits operation (locked/pending)
- `2306` policy errors (unsupported status change, duplicate NS, unsupported keyData)

## `domain:renew`

### Purpose
Extend validity period.

### Required Fields
- `domain:name`
- `domain:curExpDate` (must match current expiration)
- optional `domain:period`

### Response (`domain:renData`)
- `name`, `exDate`

### Typical Errors
- `2201` authorization error
- `2303` domain not found
- `2306` policy errors (`curExpDate` mismatch or period too large)

### Client Convenience API (this library)

- Full payload (legacy-compatible):
  - `domain()->renew(array $request)`
- Simplified fluent form:
  - `domain()->renew($domain, $years)`
- Note: EPP still requires `curExpDate`; the client resolves it internally via domain info before sending renew.

## `domain:transfer`

### Purpose
Manage transfer lifecycle.

### Supported `op` values
- `request`
- `query`
- `cancel`
- `approve`
- `reject`

### Request Fields
- `domain:name`
- optional `domain:period` (for `request`)
- optional/required `domain:authInfo` depending on transfer stage (notably when grabbing already-initiated transfer)

### Response
- `request`/`query` generally return `domain:trnData`
- `cancel`/`approve`/`reject` may return success without `resData`

### Typical Errors
- `2202` invalid auth info
- `2301` not pending transfer
- `2303` domain not transferable/not found
- `2304` status prohibits requested transfer action
- `2306` policy error (for example, approve by current sponsor)

## `domain:delete`

### Purpose
Delete a domain object.

### Required Field
- `domain:name`

### Response
- Success (`1000` or `1001`), no `resData`.

### Typical Errors
- `2201` authorization error
- `2303` domain not found
