<?php

declare(strict_types=1);

namespace RNIDS;

use RNIDS\Config\ClientConfig;
use RNIDS\Config\ClientConfigFactory;
use RNIDS\Connection\Transport;
use RNIDS\Connection\TransportFactory;
use RNIDS\Contact\ContactService;
use RNIDS\Domain\DomainService;
use RNIDS\Host\HostService;
use RNIDS\Session\SessionService;
use RNIDS\Xml\Response\LastResponseMetadata;

final class Client
{
    private ClientConfig $clientConfig;

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
        $this->clientConfig = ClientConfigFactory::fromArray($config);
        $this->transport = (new TransportFactory())->create(
            $this->clientConfig->connectionConfig,
            $this->clientConfig->tlsConfig,
        );

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
                'clientId' => $this->clientConfig->username,
                'extensionUris' => $this->clientConfig->extensionUris,
                'language' => $this->clientConfig->language,
                'objectUris' => $this->clientConfig->objectUris,
                'password' => $this->clientConfig->password,
                'version' => $this->clientConfig->version,
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
}
