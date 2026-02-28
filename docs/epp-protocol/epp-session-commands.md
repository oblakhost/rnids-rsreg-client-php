# Session Commands

Reference for EPP session lifecycle in RsReg2.

## `hello`

### Purpose
Retrieve server greeting/capability metadata at any time.

### Request
- Empty `<hello/>` element under `<epp>`.

### Response
- `<greeting>` with:
  - `svID`, `svDate`
  - `svcMenu/version`, `svcMenu/lang`
  - supported `objURI` values
  - optional `svcExtension/extURI`
  - `dcp` privacy policy section

### Typical Errors
- `2001` — Command syntax error.

## `login`

### Purpose
Open authenticated client session.

### Required Request Fields
- `clID`
- `pw`

### Optional/Negotiated Fields
- `options/version` (must match greeting)
- `options/lang` (`en` or `sr-Latn-RS`)

### Response
- Success: `1000` and no `resData`.

### Typical Errors
- `2001` — Command syntax error.
- `2002` — Command use error (already logged in).
- `2200` — Authentication error.

## `logout`

### Purpose
Terminate active session.

### Request
- `<command><logout/></command>`

### Response
- Success: `1500` and session is ended.

### Typical Errors
- `2001` — Command syntax error.

## Session Flow Notes

Typical command order:

1. Connect transport
2. Receive greeting / send `hello`
3. `login`
4. Perform object commands
5. `logout`

For this client implementation, include `clTRID` on command requests for traceability.
