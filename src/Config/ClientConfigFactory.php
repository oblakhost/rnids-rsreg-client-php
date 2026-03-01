<?php

declare(strict_types=1);

namespace RNIDS\Config;

use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\TlsConfig;
use RNIDS\Xml\NamespaceRegistry;

final class ClientConfigFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): ClientConfig
    {
        return new ClientConfig(
            connectionConfig: new ConnectionConfig(
                self::requireString($config, 'host'),
                self::optionalInt($config, 'port', 700),
                self::optionalInt($config, 'connectTimeoutSeconds', 10),
                self::optionalInt($config, 'readTimeoutSeconds', 20),
            ),
            username: self::requireString($config, 'username'),
            password: self::requireString($config, 'password'),
            language: self::optionalString($config, 'language', 'en'),
            version: self::optionalString($config, 'version', '1.0'),
            objectUris: self::optionalStringList(
                $config,
                'objectUris',
                [
                    NamespaceRegistry::DOMAIN,
                    NamespaceRegistry::CONTACT,
                    NamespaceRegistry::HOST,
                ],
            ),
            extensionUris: self::optionalStringList(
                $config,
                'extensionUris',
                [
                    NamespaceRegistry::RNIDS,
                ],
            ),
            tlsConfig: self::buildTlsConfig($config),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function requireString(array $config, string $key): string
    {
        $value = $config[$key] ?? null;

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Client config key "%s" must be a non-empty string.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function optionalString(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? null;

        if (null === $value) {
            return $default;
        }

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Client config key "%s" must be a non-empty string.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function optionalInt(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? null;

        if (null === $value) {
            return $default;
        }

        if (!\is_int($value) || $value <= 0) {
            throw new \InvalidArgumentException(
                \sprintf('Client config key "%s" must be a positive integer.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $config
     * @param list<string> $default
     *
     * @return list<string>
     */
    private static function optionalStringList(array $config, string $key, array $default): array
    {
        $value = $config[$key] ?? null;

        if (null === $value) {
            return $default;
        }

        if (!\is_array($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Client config key "%s" must be a list of strings.', $key),
            );
        }

        $result = [];

        foreach ($value as $item) {
            $result[] = self::requireListItemString($key, $item);
        }

        return $result;
    }

    private static function requireListItemString(string $key, mixed $value): string
    {
        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Client config key "%s" must contain only non-empty strings.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function buildTlsConfig(array $config): ?TlsConfig
    {
        $tls = $config['tls'] ?? null;

        if (!\is_array($tls)) {
            return null;
        }

        $certPath = $tls['clientCertificatePath'] ?? null;

        if (!\is_string($certPath) || '' === $certPath) {
            return null;
        }

        return new TlsConfig(
            $certPath,
            self::tlsOptionalString($tls, 'clientCertificatePassword'),
            self::tlsOptionalString($tls, 'caFilePath'),
            self::tlsOptionalString($tls, 'peerName'),
            self::tlsOptionalBool($tls, 'allowSelfSigned') ?? false,
            self::tlsOptionalBool($tls, 'verifyPeer'),
            self::tlsOptionalBool($tls, 'verifyPeerName'),
        );
    }

    /**
     * @param array<string, mixed> $tls
     */
    private static function tlsOptionalString(array $tls, string $key): ?string
    {
        $value = $tls[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $tls
     */
    private static function tlsOptionalBool(array $tls, string $key): ?bool
    {
        if (!\array_key_exists($key, $tls)) {
            return null;
        }

        return (bool) $tls[$key];
    }
}
