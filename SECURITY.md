# Security policy

This independent third-party library is not affiliated with, endorsed by, or
supported by RNIDS. This policy covers vulnerabilities in this repository's code;
registry security incidents should go through the relevant authorized registry
or registrar channel.

## Supported versions

Security fixes are provided for the latest stable 2.x release through September 30,
2028. Upgrade older 2.x patches to receive fixes. Version 1.x and earlier are
superseded; see [SUPPORT.md](SUPPORT.md) and [UPGRADING.md](UPGRADING.md).
The LTS designation remains pending the acceptance work described in SUPPORT.md.

## Reporting a vulnerability

Use GitHub's private
[Report a vulnerability form](https://github.com/oblakhost/rnids-rsreg-client-php/security/advisories/new).
Reports go to this project's maintainers, not to RNIDS. Include the affected
version, PHP version, impact, and a minimal reproduction using synthetic data.

Keep client certificates/private keys, certificate passphrases, account passwords,
domain authorization codes, and registrant personal data out of reports and public
issues. Share only redacted transaction metadata and the minimum XML needed to
explain the problem. Do not publish exploit details in a public issue before the
maintainers have had an opportunity to assess and coordinate a fix.

Maintainers assess reports and coordinate disclosure through the private advisory.
Published fixes identify affected versions and upgrade instructions. Response and
release timing depend on the severity and available maintainer capacity.

## Secure operation

Use TLS peer and hostname verification with an appropriate trusted CA and protect
the client key at rest. CLI and library defaults keep verification enabled; relax
these settings only through an explicit configuration for a controlled test peer.
Use an upstream-supported PHP/OpenSSL environment and keep installed dependencies
updated. The library's compatibility floor is not a security-support promise for
the underlying runtime.
