<?php

declare(strict_types=1);

namespace RNIDS\Cli;

use RNIDS\Config\ClientConfigFactory;

/**
 * Reads the rsreg executable's environment configuration.
 *
 * @internal
 * @phpstan-import-type ClientOptions from ClientConfigFactory
 */
final class Environment
{
    /** @return ClientOptions */
    public static function clientConfig(): array
    {
        $host = self::optionalString('RNIDS_EPP_HOST') ?? 'epp-test.rnids.rs';
        $testEndpoint = 'epp-test.rnids.rs' === \strtolower($host);
        $greetingMode = self::optionalString(
            'RNIDS_EPP_GREETING_MODE',
        ) ?? ($testEndpoint ? 'hello' : 'unsolicited');

        if (!\in_array($greetingMode, [ 'hello', 'unsolicited' ], true)) {
            throw new \InvalidArgumentException('RNIDS_EPP_GREETING_MODE must be hello or unsolicited.');
        }

        return [
            'connectTimeoutSeconds' => self::positiveInt('RNIDS_EPP_CONNECT_TIMEOUT', 10),
            'greetingMode' => $greetingMode,
            'host' => $host,
            'password' => self::requiredString('RNIDS_EPP_PASSWORD'),
            'port' => self::positiveInt('RNIDS_EPP_PORT', 700, 65535),
            'readTimeoutSeconds' => self::positiveInt('RNIDS_EPP_READ_TIMEOUT', 20),
            'requireClientTransactionId' => self::boolean(
                'RNIDS_EPP_REQUIRE_CLIENT_TRANSACTION_ID',
                !$testEndpoint,
            ),
            'tls' => [
                'allowSelfSigned' => self::boolean('RNIDS_EPP_TLS_ALLOW_SELF_SIGNED', false),
                'caFilePath' => self::readableFile('RNIDS_EPP_CA_CERT_PATH'),
                'clientCertificatePassword' => self::optionalString('RNIDS_EPP_CLIENT_CERT_PASSWORD'),
                'clientCertificatePath' => self::readableFile('RNIDS_EPP_CLIENT_CERT_PATH', true),
                'peerName' => self::optionalString('RNIDS_EPP_TLS_PEER_NAME'),
                'verifyPeer' => self::boolean('RNIDS_EPP_TLS_VERIFY_PEER', true),
                'verifyPeerName' => self::boolean('RNIDS_EPP_TLS_VERIFY_PEER_NAME', true),
            ],
            'username' => self::requiredString('RNIDS_EPP_USERNAME'),
        ];
    }

    /** @param ClientOptions $config */
    public static function debugSummary(array $config): string
    {
        if (!self::boolean('RNIDS_EPP_TLS_DEBUG', false)) {
            return '';
        }

        $tls = $config['tls'] ?? [];

        return \sprintf(
            'TLS debug: cert=%s ca=%s allowSelfSigned=%s verifyPeer=%s verifyPeerName=%s peerName=%s' . PHP_EOL,
            $tls['clientCertificatePath'] ?? '<unset>',
            $tls['caFilePath'] ?? '<system-trust>',
            $tls['allowSelfSigned'] ?? false ? 'true' : 'false',
            $tls['verifyPeer'] ?? true ? 'true' : 'false',
            $tls['verifyPeerName'] ?? true ? 'true' : 'false',
            $tls['peerName'] ?? $config['host'],
        );
    }

    private static function requiredString(string $name): string
    {
        return self::optionalString($name)
            ?? throw new \InvalidArgumentException('Missing ' . $name . ' environment variable.');
    }

    private static function optionalString(string $name): ?string
    {
        $value = \getenv($name);

        return false === $value || '' === \trim($value) ? null : $value;
    }

    private static function readableFile(string $name, bool $required = false): ?string
    {
        $path = $required ? self::requiredString($name) : self::optionalString($name);

        if (null !== $path && (!\is_file($path) || !\is_readable($path))) {
            throw new \InvalidArgumentException($name . ' must name a readable file: ' . $path);
        }

        return $path;
    }

    private static function positiveInt(string $name, int $default, int $maximum = PHP_INT_MAX): int
    {
        $value = self::optionalString($name);

        if (null === $value) {
            return $default;
        }

        $parsed = \filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [ 'max_range' => $maximum, 'min_range' => 1 ],
        ]);

        if (false === $parsed) {
            throw new \InvalidArgumentException(
                $name . ' must be a positive integer no greater than ' . $maximum . '.',
            );
        }

        return $parsed;
    }

    private static function boolean(string $name, bool $default): bool
    {
        $value = self::optionalString($name);

        if (null === $value) {
            return $default;
        }

        return match (\strtolower(\trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => throw new \InvalidArgumentException(
                $name . ' must be one of: 1,true,yes,on,0,false,no,off.',
            ),
        };
    }
}
