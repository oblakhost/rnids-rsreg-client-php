# LTS acceptance procedure

The 2.x line is maintained through **September 30, 2028**. Its LTS designation is
deferred until the remaining live registry workflows can be verified. This is an
independent third-party project's support decision, not RNIDS certification.
See [SUPPORT.md](../SUPPORT.md) for the compatibility commitment and the
[registry record](registry-compatibility.md) for completed checks.

Task status and evidence are tracked in Beads: `rnids-epp-client-0aa` depends on
the three acceptance tasks below. Resume them when a transfer test window and the
required registrar access are available. A suspected working transfer path is not
recorded acceptance; defects found during that session can be fixed in a compatible
2.x patch before designation.

## What the maintainer needs to arrange

| Bead | Operator prerequisite | Completion evidence |
| --- | --- | --- |
| `0aa.1` — transfers | A second authorized test registrar account, or a cooperating operator who can perform its part of the transfer workflow; access to the test transfer-code/approval channel | Completed transfer with the new sponsor confirmed, plus separate canceled and rejected transfer scenarios with the original sponsor retained |
| `0aa.2` — secure mode | An authorized operator who can complete the registry's approval process for a disposable domain's secure-to-normal change | Info shows `operationMode: normal` after approval and the relevant pending change is resolved |
| `0aa.3` — poll acknowledgment | An exact current queue message ID approved for acknowledgment; unrelated messages must first be handled by the account owner, or use an isolated test queue | The guarded acknowledgment test passes and the queue advances or becomes empty |

The existing test account and client certificate have already authenticated with
peer and hostname verification enabled. A second account must have its own
authorized credentials and any certificate/network access required by the registry.
Configure credentials through the existing secure environment; do not put them in
issue descriptions, reports, or repository files. Cooperation with another operator
does not require sharing their credentials with this project.

## Transfer scenarios

Agree on the RNIDS test workflow and the operator responsible for each action
before creating fixtures. The SDK exposes EPP operations directly; method names
alone do not establish which registrar should invoke them for a particular RNIDS
transfer stage. The [domain API](api-domain.md#transfers) documents the methods.

Use separate disposable domains or fresh transfer attempts for successful,
canceled, and rejected scenarios. Register every intended fixture in an ownership
ledger before sending a mutation. For each scenario, capture the initial sponsor
and status, then the relevant request/query/action results and final domain info.
Confirm the expected final sponsor and state instead of relying on a success code.
Process associated poll messages only with the account owner's authorization.

`getCode()` is a compatibility alias that submits a transfer request; its return
value is a mapped transfer response, not a generated authorization code. Obtain
any required code through the registry's authorized workflow. Keep codes out of
public reports.

## Secure-mode scenario

Once an operator can complete approval, use a disposable domain that is not
already pending deletion. Record the change request, confirm its pending state,
have the authorized operator complete the external step, and inspect the domain
again. The earlier probe established normal-to-secure completion and acceptance
of secure-to-normal, but retained `secure` with `pendingUpdate` on readback.

The earlier diagnostic fixtures were submitted for cleanup. Do not assume their
old approval requests remain usable. Start a fresh recorded scenario when needed;
do not repeat a mutation whose outcome is unknown after a connection failure.

## Poll acknowledgment and the automated suite

Use the [live test configuration](../CONTRIBUTING.md#live-integration-suite).
Inspect the queue through `session()->poll()` and obtain approval for the exact
current head. Set `RNIDS_EPP_POLL_ACK_MESSAGE_ID` to that ID. Run just the existing
guarded acknowledgment case when confirming this workflow:

```sh
php vendor/bin/phpunit --testsuite integration \
  --filter testPollAcknowledgesOnlyExplicitlyApprovedMessage --fail-on-skipped
```

The test refuses a different head and does not drain the queue. A successful run
verifies the acknowledgment result code. Then read the queue again and record
either `1300` (empty) or a different head ID to establish that the message was
dequeued; the current test does not perform that final readback. The approved ID
must be refreshed before a later run.
When all prerequisites are available, run the full suite with another suitable
approved message and retain a report and resource ledger:

```sh
mkdir -p build
RNIDS_EPP_RESOURCE_LEDGER="$PWD/build/lts-resources.json" \
  composer test:live -- --log-junit build/lts-live.xml
```

The full suite creates and cleans up disposable resources, including a renewal.
It does not automate the two-registrar or external approval workflows above.
An explicit live run exits nonzero when a case is skipped; retain that distinction
in the acceptance record.

## Evidence, cleanup, and designation

Record the exact tested revision, endpoint, verified TLS settings, registrar
roles, fixture identifiers, result metadata, final object state, test outcomes,
and cleanup ledger in the corresponding Bead. Save metadata before another
command replaces it. Exclude credentials, authorization codes, and personal data
from shared reports.

Accepted domain deletion can remain pending; linked contacts may then reject
deletion. Revisit only recorded owned resources after the registry completes
deletion. Verify contact absence through info returning `2303`, since its ID can
remain unavailable for reuse. Retained fixtures are tracked in
`rnids-epp-client-23d`.

Once the three acceptance tasks are satisfied, fix and regression-test any defects,
run the local quality, coverage, and distribution gates for the final revision,
and update the registry record and support designation together. Keep the PHP 8.1
floor, 2.x public API commitment, and September 2028 maintenance end unchanged.

Empty contact `authInfo` remains an explicit limitation: RNIDS accepted the command
but info does not expose the resulting value. The library documents submission
behavior without claiming verified credential clearing. This limitation does not
stand in for a successful domain transfer test.
