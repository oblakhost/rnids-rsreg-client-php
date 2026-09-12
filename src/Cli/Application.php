<?php

declare(strict_types=1);

namespace RNIDS\Cli;

use RNIDS\Client;
use RNIDS\Connection\Transport;

/**
 * Internal command runner for the rsreg executable.
 *
 * @internal
 */
final class Application
{
    private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    private const COMMANDS = [
        'session:hello', 'session:poll',
        'domain:check', 'domain:info', 'domain:register', 'domain:renew', 'domain:update', 'domain:delete',
        'domain:transfer', 'domain:transfer:request', 'domain:transfer:query', 'domain:transfer:approve',
        'domain:transfer:cancel', 'domain:transfer:reject',
        'contact:check', 'contact:info', 'contact:create', 'contact:update', 'contact:delete',
        'host:check', 'host:info', 'host:create', 'host:update', 'host:delete',
    ];

    public function __construct(private readonly ?Transport $transport = null)
    {
    }

    /**
     * @param list<string> $arguments Command name and its positional arguments, excluding the executable.
     *
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    public function run(array $arguments): array
    {
        $command = \trim($arguments[0] ?? '');

        if (1 === \count($arguments) && \in_array($command, [ '--help', '-h', 'help' ], true)) {
            return [ 'exitCode' => 0, 'stderr' => '', 'stdout' => $this->help() ];
        }

        return $this->runCommand($command, $arguments);
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    private function runCommand(string $command, array $arguments): array
    {
        $client = null;
        $exitCode = 0;
        $stdout = '';
        $stderr = '';

        try {
            $argument = $this->validateArguments($command, $arguments);
            $config = Environment::clientConfig();
            $stderr = Environment::debugSummary($config);
            $client = new Client($config, $this->transport);
            $client->init();
            $stdout = $this->execute($client, $command, $argument) . PHP_EOL;
        } catch (\Throwable $error) {
            $exitCode = $this->errorExitCode($error);
            $stderr .= ('' === $command ? 'rsreg' : $command) . ' failed: ' . $error->getMessage() . PHP_EOL;
        } finally {
            try {
                $client?->close();
            } catch (\Throwable $error) {
                $exitCode = 1;
                $stderr .= 'Session close failed: ' . $error->getMessage() . PHP_EOL;
            }
        }

        return [ 'exitCode' => $exitCode, 'stderr' => $stderr, 'stdout' => $stdout ];
    }

    private function errorExitCode(\Throwable $error): int
    {
        return $error instanceof \InvalidArgumentException || $error instanceof \JsonException ? 2 : 1;
    }

    private function help(): string
    {
        return <<<'HELP'
Usage: vendor/bin/rsreg <command> [argument]

Independent third-party RSreg EPP client, unaffiliated with RNIDS.

  session:hello | session:poll
  domain:check <comma-separated-domains>
  domain:info | domain:delete <domain>
  domain:register | domain:renew | domain:update <json-object>
  domain:transfer <json-object with type: request|query|approve|cancel|reject>
  domain:transfer:request | domain:transfer:query | domain:transfer:approve <json-object>
  domain:transfer:cancel | domain:transfer:reject <json-object>
  contact:check <comma-separated-ids>
  contact:info | contact:delete <contact-id>
  contact:create | contact:update <json-object>
  host:check <comma-separated-hosts>
  host:info | host:delete <hostname>
  host:create | host:update <json-object>
  --help | -h | help

Examples:
  vendor/bin/rsreg domain:renew '{"name":"example.rs","years":1}'
  vendor/bin/rsreg domain:renew '{"name":"example.rs","years":2,"expiry":"2026-09-12"}'
  vendor/bin/rsreg domain:transfer '{"name":"example.rs","authInfo":"secret","type":"request"}'
  vendor/bin/rsreg domain:transfer:query '{"name":"example.rs"}'

Required environment: RNIDS_EPP_USERNAME, RNIDS_EPP_PASSWORD, RNIDS_EPP_CLIENT_CERT_PATH.
Optional: RNIDS_EPP_CLIENT_CERT_PASSWORD, RNIDS_EPP_CA_CERT_PATH (otherwise system trust),
  RNIDS_EPP_HOST (epp-test.rnids.rs), RNIDS_EPP_PORT (700),
  RNIDS_EPP_CONNECT_TIMEOUT (10 seconds), RNIDS_EPP_READ_TIMEOUT (20 seconds),
  RNIDS_EPP_GREETING_MODE, RNIDS_EPP_REQUIRE_CLIENT_TRANSACTION_ID,
  RNIDS_EPP_TLS_PEER_NAME (host), RNIDS_EPP_TLS_DEBUG (false).
TLS verifies the certificate and hostname by default. Explicit development overrides:
  RNIDS_EPP_TLS_ALLOW_SELF_SIGNED=true (default false),
  RNIDS_EPP_TLS_VERIFY_PEER=false, RNIDS_EPP_TLS_VERIFY_PEER_NAME=false (defaults true).
The test endpoint defaults to greeting mode hello and permits missing response clTRID.
Other hosts default to unsolicited greeting and require response clTRID.

Success writes JSON to stdout. Errors go to stderr. Exit codes: 0 success, 1 runtime or
registry/cleanup failure, 2 invalid command, payload, or configuration. See docs/cli.md.

HELP;
    }

    /** @param list<string> $arguments */
    private function validateArguments(string $command, array $arguments): string
    {
        if (!\in_array($command, self::COMMANDS, true)) {
            throw new \InvalidArgumentException('Unknown command. Use --help for usage.');
        }

        $expectedCount = \str_starts_with($command, 'session:') ? 1 : 2;

        if ($expectedCount !== \count($arguments)) {
            throw new \InvalidArgumentException('Incorrect number of arguments. Use --help for usage.');
        }

        $argument = $arguments[1] ?? '';

        if (1 === $expectedCount) {
            return $argument;
        }

        if ('' === \trim($argument)) {
            throw new \InvalidArgumentException('A non-empty argument is required.');
        }

        return $this->normalizePayload($command, $argument);
    }

    private function normalizePayload(string $command, string $argument): string
    {
        if (\str_ends_with($command, ':check')) {
            $this->parseList($argument);

            return $argument;
        }

        if (\str_ends_with($command, ':info') || \str_ends_with($command, ':delete')) {
            return $argument;
        }

        $payload = \json_decode($argument, false, 512, JSON_THROW_ON_ERROR);

        if (!$payload instanceof \stdClass) {
            throw new \InvalidArgumentException('Payload must be a JSON object.');
        }

        match (true) {
            'domain:renew' === $command => $this->validateRenew($payload),
            \str_starts_with($command, 'domain:transfer') => $this->validateTransfer($command, $payload),
            default => null,
        };

        return match ($command) {
            'contact:update' => $this->normalizeContactUpdate($payload),
            'domain:register' => $this->normalizeRegister($payload),
            default => $argument,
        };
    }

    private function execute(Client $client, string $command, string $argument): string
    {
        if ('domain:renew' === $command) {
            return $this->executeRenew($client, \json_decode($argument));
        }

        // The SDK validates the command-specific array shapes at this untrusted JSON boundary.
        $response = match ($command) {
            'session:hello' => $client->session()->hello(),
            'session:poll' => $client->session()->poll(),
            'domain:check' => $client->domain()->check([ 'names' => $this->parseList($argument) ]),
            'domain:info' => $client->domain()->info(\trim($argument)),
            'domain:register' => $client->domain()->register(
                \json_decode($argument, true, 512, JSON_THROW_ON_ERROR),
            ),
            'domain:update' => $client->domain()->update(
                \json_decode($argument, true, 512, JSON_THROW_ON_ERROR),
            ),
            'domain:delete' => $client->domain()->delete(\trim($argument)),
            'contact:check' => $client->contact()->check([ 'ids' => $this->parseList($argument) ]),
            'contact:info' => $client->contact()->info(\trim($argument)),
            'contact:create' => $client->contact()->create(
                \json_decode($argument, true, 512, JSON_THROW_ON_ERROR),
            ),
            'contact:update' => $client->contact()->update(
                \json_decode($argument, true, 512, JSON_THROW_ON_ERROR),
            ),
            'contact:delete' => $client->contact()->delete(\trim($argument)),
            'host:check' => $client->host()->check([ 'names' => $this->parseList($argument) ]),
            'host:info' => $client->host()->info(\trim($argument)),
            'host:create' => $client->host()->create(\json_decode($argument, true, 512, JSON_THROW_ON_ERROR)),
            'host:update' => $client->host()->update(\json_decode($argument, true, 512, JSON_THROW_ON_ERROR)),
            'host:delete' => $client->host()->delete(\trim($argument)),
            default => null,
        };

        return null === $response
            ? $this->executeTransfer($client, $command, \json_decode($argument))
            : \json_encode($response, self::JSON_FLAGS);
    }

    private function executeRenew(Client $client, \stdClass $payload): string
    {
        return \json_encode($client->domain()->renew(
            $this->requiredName($payload),
            $payload->years ?? 1,
            $payload->expiry ?? null,
        ), self::JSON_FLAGS);
    }

    private function executeTransfer(Client $client, string $command, \stdClass $payload): string
    {
        $action = $this->transferAction($command, $payload);
        $name = $this->requiredName($payload);
        $authInfo = $payload->authInfo ?? null;
        $domain = $client->domain();
        $response = match ($action) {
            'request' => $domain->transferRequest($name, $authInfo),
            'query' => $domain->transferQuery($name, $authInfo),
            'approve' => $domain->transferApprove($name, $authInfo),
            'cancel' => $domain->transferCancel($name, $authInfo),
            'reject' => $domain->transferReject($name, $authInfo),
        };

        return \json_encode($response, self::JSON_FLAGS);
    }

    private function validateRenew(\stdClass $payload): void
    {
        $this->assertKnownKeys($payload, [ 'name', 'years', 'expiry' ]);
        $this->requiredName($payload);
        $years = $payload->years ?? 1;

        if (!\is_int($years) || $years < 1 || $years > 10) {
            throw new \InvalidArgumentException('Renew years must be an integer between 1 and 10.');
        }

        if (isset($payload->expiry) && (!\is_string($payload->expiry) || '' === \trim($payload->expiry))) {
            throw new \InvalidArgumentException(
                'Renew expiry must be a non-empty date string when provided.',
            );
        }
    }

    private function validateTransfer(string $command, \stdClass $payload): void
    {
        $this->assertKnownKeys($payload, [ 'name', 'authInfo', 'type' ]);
        $this->requiredName($payload);
        $this->transferAction($command, $payload);

        $authInfo = $payload->authInfo ?? null;

        if (null !== $authInfo && (!\is_string($authInfo) || '' === \trim($authInfo))) {
            throw new \InvalidArgumentException(
                'Transfer authInfo must be a non-empty string when provided.',
            );
        }
    }

    /** @return 'request'|'query'|'approve'|'cancel'|'reject' */
    private function transferAction(string $command, \stdClass $payload): string
    {
        $explicitAction = \explode(':', $command)[2] ?? null;
        $action = $explicitAction ?? $payload->type ?? null;

        if (!\in_array($action, [ 'request', 'query', 'approve', 'cancel', 'reject' ], true)) {
            throw new \InvalidArgumentException(
                'Transfer type must be request, query, approve, cancel, or reject.',
            );
        }

        if (null !== $explicitAction && isset($payload->type) && $payload->type !== $explicitAction) {
            throw new \InvalidArgumentException('Transfer type conflicts with the explicit command action.');
        }

        return $action;
    }

    private function requiredName(\stdClass $payload): string
    {
        if (!isset($payload->name) || !\is_string($payload->name) || '' === \trim($payload->name)) {
            throw new \InvalidArgumentException('Payload name must be a non-empty domain name.');
        }

        return $payload->name;
    }

    /** @param list<string> $allowed */
    private function assertKnownKeys(\stdClass $payload, array $allowed): void
    {
        if ([] !== \array_diff(\array_keys((array) $payload), $allowed)) {
            throw new \InvalidArgumentException(
                'Unknown payload key. Allowed keys: ' . \implode(', ', $allowed) . '.',
            );
        }
    }

    private function normalizeRegister(\stdClass $payload): string
    {
        if (!\property_exists($payload, 'years')) {
            return \json_encode($payload, JSON_THROW_ON_ERROR);
        }

        $years = $payload->years;

        if (!\is_int($years) || $years < 1 || $years > 10) {
            throw new \InvalidArgumentException('Register years must be an integer between 1 and 10.');
        }

        if (\property_exists($payload, 'period') || \property_exists($payload, 'periodUnit')) {
            throw new \InvalidArgumentException(
                'Register years cannot be combined with period or periodUnit.',
            );
        }

        $payload->period = $years;
        unset($payload->years);

        return \json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function normalizeContactUpdate(\stdClass $payload): string
    {
        if (!\property_exists($payload, 'change')) {
            return \json_encode($payload, JSON_THROW_ON_ERROR);
        }

        if (!$payload->change instanceof \stdClass) {
            throw new \InvalidArgumentException('Contact change must be a JSON object.');
        }

        $this->assertKnownKeys($payload->change, [
            'postalInfo', 'voice', 'fax', 'email', 'authInfo', 'disclose', 'extension',
        ]);

        foreach (\get_object_vars($payload->change) as $key => $value) {
            if (\property_exists($payload, $key)) {
                throw new \InvalidArgumentException('Contact change conflicts with top-level key: ' . $key);
            }

            $payload->{$key} = $value;
        }

        unset($payload->change);

        return \json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /** @return non-empty-list<non-empty-string> */
    private function parseList(string $argument): array
    {
        $items = \array_values(\array_filter(
            \array_map('trim', \explode(',', $argument)),
            static fn(string $item): bool => '' !== $item,
        ));

        if ([] === $items) {
            throw new \InvalidArgumentException('Check requires at least one non-empty name or id.');
        }

        return $items;
    }
}
