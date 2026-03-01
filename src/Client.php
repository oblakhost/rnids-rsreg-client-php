<?php

declare(strict_types=1);

namespace RNIDS;

use RNIDS\Connection\ConnectionConfig;
use RNIDS\Connection\TlsConfig;
use RNIDS\Connection\Transport;
use RNIDS\Contact\ContactService;
use RNIDS\Domain\DomainService;
use RNIDS\Host\HostService;
use RNIDS\Session\SessionService;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\Response\LastResponseMetadata;

final class Client
{
    private ?Transport $transport = null;

    private SessionService $sessionService;

    private DomainService $domainService;

    private ContactService $contactService;

    private HostService $hostService;

    private LastResponseMetadata $lastResponseMetadata;

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
     * Creates and initializes a connected, authenticated RNIDS EPP client instance.
     *
     * @param array<string, mixed> $config
     *   Client configuration including host/credentials and optional TLS/runtime settings.
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

        $this->lastResponseMetadata = new LastResponseMetadata();
        $this->sessionService = new SessionService(
            transport: $this->transport,
            lastResponseMetadata: $this->lastResponseMetadata,
        );
        $this->domainService = new DomainService(
            transport: $this->transport,
            lastResponseMetadata: $this->lastResponseMetadata,
        );
        $this->contactService = new ContactService(
            transport: $this->transport,
            lastResponseMetadata: $this->lastResponseMetadata,
        );
        $this->hostService = new HostService(
            transport: $this->transport,
            lastResponseMetadata: $this->lastResponseMetadata,
        );
        $this->transport->connect();
        $this->sessionService->hello();
        $this->sessionService->login([
            'clientId' => self::requireString($config, 'username'),
            'extensionUris' => self::optionalStringList(
                $config,
                'extensionUris',
                [
                    NamespaceRegistry::RNIDS,
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

    /**
     * Ensures an authenticated session is gracefully closed on object destruction.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Returns the active transport instance.
     */
    public function transport(): Transport
    {
        return $this->transport ?? throw new \RuntimeException('Transport is not initialized.');
    }

    /**
     * Logs out the session when needed and disconnects the transport.
     */
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

    /**
     * Returns the fluent session service.
     */
    public function session(): SessionService
    {
        return $this->sessionService;
    }

    /**
     * Returns the fluent domain service.
     */
    public function domain(): DomainService
    {
        return $this->domainService;
    }

    /**
     * Returns the fluent contact service.
     */
    public function contact(): ContactService
    {
        return $this->contactService;
    }

    /**
     * Returns the fluent host (nameserver) service.
     */
    public function host(): HostService
    {
        return $this->hostService;
    }

    /**
     * Returns metadata for the latest parsed EPP response, when available.
     *
     * @return array{
     *   clientTransactionId: string|null,
     *   message: string,
     *   resultCode: int,
     *   serverTransactionId: string|null
     * }|null Latest parsed response metadata, or null when no response has been parsed yet.
     */
    public function responseMeta(): ?array
    {
        $metadata = $this->lastResponseMetadata->get();

        if (null === $metadata) {
            return null;
        }

        return [
            'clientTransactionId' => $metadata->clientTransactionId,
            'message' => $metadata->message,
            'resultCode' => $metadata->resultCode,
            'serverTransactionId' => $metadata->serverTransactionId,
        ];
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
            $this->tlsOptionalString($tls, 'clientCertificatePassword'),
            $this->tlsOptionalString($tls, 'caFilePath'),
            $this->tlsOptionalString($tls, 'peerName'),
            $this->tlsOptionalBool($tls, 'allowSelfSigned') ?? false,
            $this->tlsOptionalBool($tls, 'verifyPeer'),
            $this->tlsOptionalBool($tls, 'verifyPeerName'),
        );
    }

    /**
     * @param array<string, mixed> $tls
     */
    private function tlsOptionalString(array $tls, string $key): ?string
    {
        $value = $tls[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $tls
     */
    private function tlsOptionalBool(array $tls, string $key): ?bool
    {
        if (!\array_key_exists($key, $tls)) {
            return null;
        }

        return (bool) $tls[$key];
    }
}
