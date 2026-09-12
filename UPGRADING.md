# Upgrading to 2.x

This is an independent third-party client for RNIDS/RSreg, unaffiliated with RNIDS.
The Composer name `rnids/rsreg-epp-client` and PHP namespace `RNIDS\` are unchanged.

The maintained release line is 2.x. Update your Composer constraint to `^2.0` and
test your integration before deployment. The fluent array API remains available;
internal DTOs do not require a public payload rewrite. See [SUPPORT.md](SUPPORT.md)
for the compatibility and maintenance policy.

## Connections

Configure `tls.clientCertificatePath` for native connections. The PEM must contain
the certificate and matching private key. Set `clientCertificatePassword` when the
key is encrypted and use `caFilePath` for the registry's trusted CA where required.
TLS boolean settings must be actual PHP booleans, not strings such as `'false'`.
`allowPlaintext => true` is an explicit option for local plaintext test peers.

Initialization now waits for the actual login result; a rejected login throws
before the client is returned. Session logout and fatal I/O/protocol errors
invalidate cached service references until `init()` reconnects the client.

For the RNIDS development endpoint, configure the observed compatibility behavior:

```php
$config['greetingMode'] = 'hello';
$config['requireClientTransactionId'] = false;
```

The defaults remain an unsolicited greeting and mandatory matching response
transaction IDs. The second option accepts omitted IDs only; a supplied mismatched
ID still fails. Confirm settings for the endpoint you use rather than assuming
development behavior applies to production. TLS verification remains enabled.

## Contacts

- Creation still normalizes IDs with the `OBL-` prefix and generates an ID if
  omitted. Use the ID returned by `create()` for update, info, and delete; all
  three now send that supplied ID literally.
- `extension.identDescription` comes from your input. Supply any desired value
  explicitly; the client no longer injects a vendor description.
- An omitted or `null` optional text update leaves the value unchanged. An empty
  string clears supported optional text fields. Required values and typed
  extension fields cannot be cleared with empty strings.
- Read the [contact response shape](docs/api-contact.md) for the current flat
  postal aliases and existing status keys.

## Domains

Unknown update keys now fail validation. Nameserver add/remove requests support
glue addresses. Send registrant changes separately from other updates, and send
DNSSEC changes separately: RNIDS gives these changes precedence over other fields.
Use `normal` or `secure` for `extension.operationMode`.

Provision DNSSEC with DS records under `dnssec.records` during registration or
`dnssec.add`, `dnssec.remove`, and `dnssec.removeAll` during updates. The RNIDS
`extension.dnsSec` boolean alone does not provision keys. This implementation
supports secDNS DS records; `keyData`, `urgent`, and `maxSigLife` are unsupported.

Use the current renewal and transfer signatures:

```php
$client->domain()->renew('example.rs', 1); // Obtains expiry when omitted.
$client->domain()->transferRequest('example.rs', $authorizationCode);
$client->domain()->transferQuery('example.rs');
```

`transferApprove()`, `transferCancel()`, and `transferReject()` expose the other
stages. Existing `transfer()`, `getCode()`, and `getState()` aliases remain supported.
Domain and host check results now recognize both numeric and lexical XML booleans,
as do contact check results. This corrects availability incorrectly reported as
false when the registry returned `avail="true"`.

Unicode domain, nameserver, and host input is encoded as ASCII Punycode on the
wire. Install `ext-intl` for Unicode input, or supply ASCII Punycode yourself.
Response names retain the registry's ASCII representation.

## CLI and integration tests

The [CLI guide](docs/cli.md) describes its environment configuration and JSON
commands. TLS verification is enabled by default, and there is no default
certificate password. Configure the endpoint and development compatibility
options explicitly. Renewal and transfer JSON inputs are translated to the current
library signatures.

`composer test` is the offline quality gate. Explicit `composer test:live` runs fail
when prerequisites are missing or a case is skipped. The test suite records owned
resources awaiting deferred deletion; successful delete acceptance does not mean
linked contacts can immediately be removed. See [Contributing](CONTRIBUTING.md).

Live CI runs now require a manual workflow dispatch with `run_live=true`.
The former `RNIDS_RUN_LIVE` repository variable no longer starts mutations on
push. Release publication waits for the offline quality gate, including checks of
the extracted distribution artifacts.

For application recovery after failed mutations, follow the
[operation outcome guidance](SUPPORT.md#operation-outcomes). Do not automatically
replay a command whose result was lost when a connection failed.
