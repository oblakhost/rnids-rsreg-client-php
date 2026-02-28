# Host Commands

Reference for `host:*` operations in RsReg2.

## `host:check`

### Purpose
Determine availability/existence of host objects.

### Request
- `<check><host:check>` with one or more `<host:name>` values.

### Response (`host:chkData`)
- Repeating `<cd>` entries:
  - `<name avail="1|0">`
  - optional `<reason lang="en|sr-Latn-RS">`

### Typical Errors
- `2001` syntax
- `2005` invalid DNS name syntax
- `2303` missing host name parameter

### Client Convenience API (this library)

- Full payload (legacy-compatible):
  - `host()->check(array $request)`
- Simplified fluent form:
  - `host()->check($hostname)` or `host()->check([$hostname1, $hostname2])`

## `host:create`

### Purpose
Create host object.

### Required Request Fields
- `host:name`

### Optional Request Fields
- one or more `host:addr` values (`ip="v4|v6"`, default `v4`)

### Response (`host:creData`)
- `name`, `crDate`

### Typical Errors
- `2005` invalid DNS name
- `2302` object exists

### Client Convenience API (this library)

- Full payload (legacy-compatible):
  - `host()->create(array $request)`
- Simplified fluent form:
  - `host()->create($hostname, ?string $ipv4 = null, ?string $ipv6 = null)`

## `host:info`

### Purpose
Retrieve host details.

### Request
- `<info><host:info><host:name>...</host:name></host:info></info>`

### Response (`host:infData`)
May include:
- `name`, `roid`, `status`
- addresses (`addr`)
- ownership metadata (`clID`, `crID`, `crDate`, `upID`, `upDate`, `trDate`)

### Typical Errors
- `2005` invalid DNS name
- `2303` host does not exist

## `host:update`

### Purpose
Modify host object attributes.

### Request Structure
- `host:name`
- optional `host:add` (addr/status)
- optional `host:rem` (addr/status)
- optional `host:chg/host:name` for rename

### Behavior Notes
- Host rename should preserve associations automatically.
- Updating certain externally-associated hosts may be blocked.

### Typical Errors
- `2005` invalid DNS name
- `2303` host does not exist
- `2305` association prohibits operation (for example linked locked domains)

## `host:delete`

### Purpose
Delete host object.

### Required Field
- `host:name`

### Response
- Success (`1000`/`1001`) without `resData`.

### Typical Errors
- `2005` invalid DNS name
- `2303` host does not exist
- `2305` host is associated with other objects
