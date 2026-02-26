# Finance and Poll Commands

Reference for finance info and message queue handling in RsReg2.

## `finance:info`

### Purpose
Retrieve registrar account balance.

### Request
- `<info><finance:info/></info>` under EPP command.

### Response (`finance:infData`)
- `balance` (decimal current account balance)

### Typical Errors
- `2001` command syntax error

## `poll` (request)

### Purpose
Fetch first queued service message for client.

### Request
- `<poll op="req"/>`

### Success Responses
1. `1301` — message available, acknowledge required
   - includes `<msgQ count="..." id="...">`
   - includes `qDate` and `msg`
   - may include `resData`
2. `1300` — no queued messages

### Notes
- Queue processing is explicit: each fetched message must be acknowledged.
- Message format is generally `Code:Message` (for example `M100:Domain domen.rs registration successful`).

### Typical Errors
- `2001` command syntax error

## `poll` (acknowledge)

### Purpose
Confirm message receipt and dequeue message.

### Request
- `<poll op="ack" msgID="..."/>`

### Response
- `1000` success
- optional `<msgQ>` with updated queue head metadata when messages remain

### Typical Errors
- `2001` command syntax error

## Poll Message Code Reference (RsReg2)

Representative messages from the RsReg2 list:

- Domain lifecycle:
  - `M100` registration successful
  - `M101` update successful
  - `M102` expired
  - `M111` renewal successful
  - `M121` deletion successful
- Domain transfer flow:
  - `M151` transfer requested
  - `M152` transfer requested by registrar
  - `M153` approved
  - `M154` cancelled
  - `M155` rejected
  - `M156` transfer successful
  - `M157` transferred away
- Domain change requests:
  - `M161/M162/M163` registrant change requested/approved/rejected
  - `M171/M172/M173` general change requested/approved/rejected
- Contact change requests:
  - `M201` contact update successful
  - `M211/M212/M213` contact change requested/approved/rejected

### TEST-only messages
- `M901` includes domain request auth-code details
- `M902` includes contact request auth-code details
