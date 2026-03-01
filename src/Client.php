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
    /** @var array<string, mixed> */
    private array $config;

    private Transport $transport;

    private SessionService $sessionService;

    private DomainService $domainService;

    private ContactService $contactService;

    private HostService $hostService;

    private LastResponseMetadata $lastResponseMetadata;

    private ?\Throwable $lastCloseError = null;

    private bool $initialized = false;

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
     * Creates, initializes, and returns a ready-to-use client instance.
     *
     * @param array<string, mixed> $config
     */
    public static function ready(array $config): self
    {
        $client = new self($config);
        $client->init();

        return $client;
    }

    /**
     * Creates a client instance with validated configuration and prepared services.
     *
     * @param array<string, mixed> $config
     *   Client configuration including host/credentials and optional TLS/runtime settings.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
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
    }

    /**
     * Ensures an authenticated session is gracefully closed on object destruction.
     */
    public function __destruct()
    {
        $this->closeInternal(true);
    }

    /**
     * Returns the configured transport instance.
     */
    public function transport(): Transport
    {
        return $this->transport;
    }

    /**
     * Logs out the session when needed and disconnects the transport.
     */
    public function close(): void
    {
        $this->closeInternal(false);
    }

    /**
     * Connects transport and authenticates the session with hello and login commands.
     */
    public function init(): void
    {
        if (true === $this->initialized) {
            return;
        }

        try {
            $this->transport->connect();
            $this->sessionService->hello();
            $this->sessionService->login([
                'clientId' => self::requireString($this->config, 'username'),
                'extensionUris' => self::optionalStringList(
                    $this->config,
                    'extensionUris',
                    [
                        NamespaceRegistry::RNIDS,
                    ],
                ),
                'language' => self::optionalString($this->config, 'language', 'en'),
                'objectUris' => self::optionalStringList(
                    $this->config,
                    'objectUris',
                    [
                        NamespaceRegistry::DOMAIN,
                        NamespaceRegistry::CONTACT,
                        NamespaceRegistry::HOST,
                    ],
                ),
                'password' => self::requireString($this->config, 'password'),
                'version' => self::optionalString($this->config, 'version', '1.0'),
            ]);
        } catch (\Throwable $throwable) {
            $this->loggedIn = false;
            $this->initialized = false;
            $this->transport->disconnect();

            throw $throwable;
        }

        $this->loggedIn = true;
        $this->initialized = true;
        $this->lastCloseError = null;
    }

    /**
     * Returns the fluent session service.
     */
    public function session(): SessionService
    {
        $this->assertInitialized();

        return $this->sessionService;
    }

    /**
     * Returns the fluent domain service.
     */
    public function domain(): DomainService
    {
        $this->assertInitialized();

        return $this->domainService;
    }

    /**
     * Returns the fluent contact service.
     */
    public function contact(): ContactService
    {
        $this->assertInitialized();

        return $this->contactService;
    }

    /**
     * Returns the fluent host (nameserver) service.
     */
    public function host(): HostService
    {
        $this->assertInitialized();

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
     * Returns the last close/destructor error when a shutdown step failed.
     */
    public function lastCloseError(): ?\Throwable
    {
        return $this->lastCloseError;
    }

    private function assertInitialized(): void
    {
        if (true === $this->initialized) {
            return;
        }

        throw new \RuntimeException('Client is not initialized. Call init() first or use Client::ready().');
    }

    private function closeInternal(bool $suppressExceptions): void
    {
        if (true !== $this->initialized) {
            return;
        }

        $error = $this->logoutAndCaptureError();
        $error = $this->disconnectAndCaptureError($error);
        $this->initialized = false;
        $this->finalizeClose($error, $suppressExceptions);
    }

    private function logoutAndCaptureError(): ?\Throwable
    {
        if (true !== $this->loggedIn) {
            return null;
        }

        try {
            $this->sessionService->logout();
        } catch (\Throwable $throwable) {
            $this->loggedIn = false;

            return $throwable;
        }

        $this->loggedIn = false;

        return null;
    }

    private function disconnectAndCaptureError(?\Throwable $error): ?\Throwable
    {
        try {
            $this->transport->disconnect();
        } catch (\Throwable $throwable) {
            if (null === $error) {
                return $throwable;
            }
        }

        return $error;
    }

    private function finalizeClose(?\Throwable $error, bool $suppressExceptions): void
    {
        if (null === $error) {
            $this->lastCloseError = null;

            return;
        }

        $this->lastCloseError = $error;

        if (true !== $suppressExceptions) {
            throw $error;
        }
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
