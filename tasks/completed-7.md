# Task 7: Expand CLI with host commands and rename entry script

## Objective
Replace the temporary `test.php` entry script with a proper `cli.php` command entrypoint and extend it to support RNIDS host operations alongside the existing domain info command. Capture the completed work in task tracking and keep project memory aligned.

## Implementation Plan
- [x] Rename `test.php` to `cli.php`.
- [x] Preserve existing `domain:info` command behavior.
- [x] Add host command support for `host:check`, `host:info`, `host:create`, `host:update`, and `host:delete`.
- [x] Add practical CLI usage help and argument validation paths.
- [x] Update repository task records with completion summary.
- [x] Optionally update soul memory with this milestone.

## Outcome
The script was renamed to `cli.php` and now supports both domain and host command execution through a single command dispatcher.

### Delivered changes
- Renamed root CLI script from `test.php` to `cli.php`.
- Added usage/help output with command examples for all supported operations.
- Kept `domain:info <domainname>` flow intact.
- Added host commands:
  - `host:check <comma-separated-hosts>`
  - `host:info <hostname>`
  - `host:create <json-payload>`
  - `host:update <json-payload>`
  - `host:delete <hostname>`
- Added JSON payload decoding helper for create/update and host-list parser for check.
- Standardized failure output to report the active command in error messages.

### Validation
- Performed PHP syntax check on `cli.php`.
