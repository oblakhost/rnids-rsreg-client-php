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
use RNIDS\Session\SessionState;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use RNIDS\Xml\CommandExecutor;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\Response\LastResponseMetadata;

/** @phpstan-import-type ClientOptions from ClientConfigFactory */
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

    private SessionState $sessionState;

    private bool $negotiateDnssec;

    /**
     * Creates, initializes, and returns a ready-to-use client instance.
     *
     * @param ClientOptions $config
     */
    public static function ready(array $config, ?Transport $transport = null): self
    {
        $client = new self($config, $transport);
        $client->init();

        return $client;
    }

    /**
     * Creates a client instance with validated configuration and prepared services.
     *
     * @param ClientOptions $config
     *   Client configuration including host/credentials and optional TLS/runtime settings.
     */
    public function __construct(array $config, ?Transport $transport = null)
    {
        $this->clientConfig = ClientConfigFactory::fromArray($config);

        if (null === $transport && null === $this->clientConfig->tlsConfig && !$this->clientConfig->allowPlaintext) {
            throw new \InvalidArgumentException(
                'TLS configuration is required. Set allowPlaintext=true only for an explicit plaintext connection.',
            );
        }

        $this->transport = $transport ?? (new TransportFactory())->create(
            $this->clientConfig->connectionConfig,
            $this->clientConfig->tlsConfig,
        );

        $this->lastResponseMetadata = new LastResponseMetadata();
        $this->sessionState = new SessionState();
        $this->negotiateDnssec = !\array_key_exists('extensionUris', $config);
        $executor = new CommandExecutor(
            $this->transport,
            null,
            $this->lastResponseMetadata,
            $this->sessionState,
            $this->clientConfig->requireClientTransactionId,
        );
        $tridGenerator = new IncrementalClTridGenerator('RNIDS-' . \bin2hex(\random_bytes(8)));
        $this->sessionService = new SessionService(
            transport: $this->transport,
            executor: $executor,
            tridGenerator: $tridGenerator,
            lastResponseMetadata: $this->lastResponseMetadata,
        );
        $this->domainService = new DomainService(
            transport: $this->transport,
            executor: $executor,
            tridGenerator: $tridGenerator,
            lastResponseMetadata: $this->lastResponseMetadata,
        );
        $this->contactService = new ContactService(
            transport: $this->transport,
            executor: $executor,
            tridGenerator: $tridGenerator,
            lastResponseMetadata: $this->lastResponseMetadata,
        );
        $this->hostService = new HostService(
            transport: $this->transport,
            executor: $executor,
            tridGenerator: $tridGenerator,
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
     * Connects, obtains the configured server greeting, and waits for the login response.
     */
    public function init(): void
    {
        if ($this->sessionState->isAuthenticated()) {
            return;
        }

        $this->lastResponseMetadata->clear();

        try {
            $this->transport->connect();
            $this->sessionState->connect();
            $greeting = 'hello' === $this->clientConfig->greetingMode
                ? $this->sessionService->hello()
                : $this->sessionService->receiveGreeting();
            $extensionUris = $this->clientConfig->extensionUris;

            $supportsDnssec = \in_array(NamespaceRegistry::SECDNS, $greeting['extensionUris'], true);

            if ($this->negotiateDnssec && $supportsDnssec) {
                $extensionUris[] = NamespaceRegistry::SECDNS;
            }

            $this->sessionService->login([
                'clientId' => $this->clientConfig->username,
                'extensionUris' => $extensionUris,
                'language' => $this->clientConfig->language,
                'objectUris' => $this->clientConfig->objectUris,
                'password' => $this->clientConfig->password,
                'version' => $this->clientConfig->version,
            ]);
        } catch (\Throwable $throwable) {
            $this->sessionState->disconnect();
            $this->disconnectAndCaptureError($throwable);

            throw $throwable;
        }

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
        if ($this->sessionState->isAuthenticated()) {
            return;
        }

        throw new \RuntimeException('Client is not initialized. Call init() first or use Client::ready().');
    }

    private function closeInternal(bool $suppressExceptions): void
    {
        if (!isset($this->sessionState) || !$this->sessionState->isConnected()) {
            return;
        }

        $error = $this->logoutAndCaptureError();
        $error = $this->disconnectAndCaptureError($error);
        $this->sessionState->disconnect();
        $this->finalizeClose($error, $suppressExceptions);
    }

    private function logoutAndCaptureError(): ?\Throwable
    {
        if (!$this->sessionState->isAuthenticated()) {
            return null;
        }

        try {
            $this->sessionService->logout();
        } catch (\Throwable $throwable) {
            return $throwable;
        }

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
