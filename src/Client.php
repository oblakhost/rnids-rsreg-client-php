<?php

declare(strict_types=1);

namespace RNIDS;

use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\TlsConfig;
use RNIDS\Connection\Transport;
use RNIDS\Domain\DomainService;
use RNIDS\Session\SessionService;
use RNIDS\Xml\NamespaceRegistry;

final class Client
{
    private ?Transport $transport = null;

    private SessionService $sessionService;

    private DomainService $domainService;

    private bool $loggedIn = false;

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
            if (!\is_string($item) || '' === \trim($item)) {
                throw new \InvalidArgumentException(
                    \sprintf('Client config key "%s" must contain only non-empty strings.', $key),
                );
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->transport = (new Builder(
            new ConnectionConfig(
                self::requireString($config, 'host'),
                self::optionalInt($config, 'port', 700),
                self::optionalInt($config, 'connectTimeoutSeconds', 10),
                self::optionalInt($config, 'readTimeoutSeconds', 20),
            ),
        ))
            ->withTlsConfig($this->buildTlsConfig($config))
            ->buildTransport();

        $this->sessionService = new SessionService($this->transport);
        $this->domainService = new DomainService($this->transport);
        $this->transport->connect();
        $this->sessionService->login([
            'clientId' => self::requireString($config, 'username'),
            'extensionUris' => self::optionalStringList(
                $config,
                'extensionUris',
                [
                    NamespaceRegistry::RNIDS_DOMAIN_EXT,
                    NamespaceRegistry::RNIDS_CONTACT_EXT,
                ],
            ),
            'language' => self::optionalString($config, 'language', 'en'),
            'objectUris' => self::optionalStringList(
                $config,
                'objectUris',
                [
                    NamespaceRegistry::DOMAIN,
                    NamespaceRegistry::CONTACT,
                    NamespaceRegistry::HOST,
                ],
            ),
            'password' => self::requireString($config, 'password'),
            'version' => self::optionalString($config, 'version', '1.0'),
        ]);

        $this->loggedIn = true;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function transport(): Transport
    {
        return $this->transport ?? throw new \RuntimeException('Transport is not initialized.');
    }

    public function close(): void
    {
        if (null === $this->transport) {
            return;
        }

        if (true === $this->loggedIn) {
            try {
                $this->sessionService->logout();
            } catch (\Throwable) {
            }

            $this->loggedIn = false;
        }

        $this->transport->disconnect();
        $this->transport = null;
    }

    public function session(): SessionService
    {
        return $this->sessionService;
    }

    public function domain(): DomainService
    {
        return $this->domainService;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildTlsConfig(array $config): ?TlsConfig
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
            isset($tls['clientCertificatePassword']) && \is_string($tls['clientCertificatePassword'])
                ? $tls['clientCertificatePassword']
                : null,
            isset($tls['caFilePath']) && \is_string($tls['caFilePath']) ? $tls['caFilePath'] : null,
            isset($tls['peerName']) && \is_string($tls['peerName']) ? $tls['peerName'] : null,
            isset($tls['allowSelfSigned']) ? (bool) $tls['allowSelfSigned'] : false,
        );
    }
}
