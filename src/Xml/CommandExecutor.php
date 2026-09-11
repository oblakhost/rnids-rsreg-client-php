<?php

declare(strict_types=1);

namespace RNIDS\Xml;

use RNIDS\Connection\Transport;
use RNIDS\Exception\MalformedResponseException;
use RNIDS\Exception\TransportException;
use RNIDS\Session\SessionState;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\LastResponseMetadata;
use RNIDS\Xml\Response\ResponseMetadata;
use RNIDS\Xml\Response\ResponseMetadataParser;

final class CommandExecutor
{
    private ResponseMetadataParser $responseMetadataParser;

    public function __construct(
        private readonly Transport $transport,
        ?ResponseMetadataParser $responseMetadataParser = null,
        private readonly ?LastResponseMetadata $lastResponseMetadata = null,
        private readonly ?SessionState $sessionState = null,
        private readonly bool $requireClientTransactionId = true,
    ) {
        $this->responseMetadataParser = $responseMetadataParser ?? new ResponseMetadataParser();
    }

    /**
     * @template T
     * @param callable(string, ResponseMetadata): T $responseParser
     * @return T
     */
    public function execute(string $xml, callable $responseParser)
    {
        $this->lastResponseMetadata?->clear();
        $request = $this->requestContext($xml);

        try {
            $this->assertSessionAvailable($request['operation']);
            $responseXml = $this->exchange($xml);
            $metadata = $this->responseMetadataParser->parse($responseXml);
            $this->validateResponse($responseXml, $metadata, $request);
            $this->lastResponseMetadata?->set($metadata);
            $this->applySessionResult($request['operation'], $metadata);
            ResultCodePolicy::assertSuccess($metadata);

            return $responseParser($responseXml, $metadata);
        } catch (\RNIDS\Exception\MalformedResponseException | \RNIDS\Exception\TransportException $exception) {
            $this->lastResponseMetadata?->clear();
            $this->invalidateConnection();

            throw $exception;
        }
    }

    /**
     * Reads the unsolicited greeting before the first command is sent.
     *
     * @template T
     * @param callable(string, ResponseMetadata): T $responseParser
     * @return T
     */
    public function receiveGreeting(callable $responseParser)
    {
        $this->lastResponseMetadata?->clear();

        try {
            $responseXml = $this->exchange(null);
            $this->assertGreeting($responseXml);
            $metadata = $this->responseMetadataParser->parse($responseXml);
            $this->lastResponseMetadata?->set($metadata);

            return $responseParser($responseXml, $metadata);
        } catch (\RNIDS\Exception\MalformedResponseException | \RNIDS\Exception\TransportException $exception) {
            $this->lastResponseMetadata?->clear();
            $this->invalidateConnection();

            throw $exception;
        }
    }

    /** @return array{operation: string, transactionId: string|null} */
    private function requestContext(string $xml): array
    {
        $xpath = XmlParser::createXPath($xml);
        $hello = $xpath->query('/epp:epp/epp:hello');

        if (false !== $hello && 1 === $hello->length) {
            return [ 'operation' => 'hello', 'transactionId' => null ];
        }

        $operations = $xpath->query('/epp:epp/epp:command/*[1]');
        $operation = false !== $operations ? $operations->item(0)?->localName : null;
        $transactionId = XmlParser::firstNodeValue($xpath, '/epp:epp/epp:command/epp:clTRID');

        if (null === $operation || null === $transactionId || '' === $transactionId) {
            throw new \InvalidArgumentException(
                'EPP commands require an operation and a client transaction ID.',
            );
        }

        return [ 'operation' => $operation, 'transactionId' => $transactionId ];
    }

    /** @param array{operation: string, transactionId: string|null} $request */
    private function validateResponse(string $xml, ResponseMetadata $metadata, array $request): void
    {
        if ('hello' === $request['operation']) {
            $this->assertGreeting($xml);

            return;
        }

        $this->assertCommandResponse($xml);
        $this->validateTransactionId($metadata->clientTransactionId, $request['transactionId']);

        if ('login' === $request['operation'] && $metadata->isSuccess() && 1000 !== $metadata->resultCode) {
            throw new \RNIDS\Exception\MalformedResponseException('Unexpected result code for EPP login.');
        }
    }

    private function assertCommandResponse(string $xml): void
    {
        $xpath = XmlParser::createXPath($xml);
        $results = $xpath->query('/epp:epp/epp:response/epp:result');
        if (false === $results || 0 === $results->length) {
            throw new \RNIDS\Exception\MalformedResponseException(
                'EPP command requires a result response, not a greeting.',
            );
        }
    }

    private function validateTransactionId(?string $actual, ?string $expected): void
    {
        if (null === $actual && !$this->requireClientTransactionId) {
            return;
        }

        if ($actual !== $expected) {
            throw new \RNIDS\Exception\MalformedResponseException(
                'EPP response client transaction ID does not match the command.',
            );
        }
    }

    private function assertGreeting(string $xml): void
    {
        $xpath = XmlParser::createXPath($xml);

        if (null === XmlParser::firstNodeValue($xpath, '/epp:epp/epp:greeting/epp:svID')) {
            throw new \RNIDS\Exception\MalformedResponseException('Expected the EPP server greeting.');
        }
    }

    private function assertSessionAvailable(string $operation): void
    {
        if (null === $this->sessionState) {
            return;
        }

        if (!$this->sessionState->isConnected()) {
            throw new \RNIDS\Exception\TransportException(
                'EPP session is disconnected. Call Client::init() to reconnect.',
            );
        }

        if (!\in_array($operation, [ 'hello', 'login' ], true) && !$this->sessionState->isAuthenticated()) {
            throw new \RNIDS\Exception\TransportException('EPP session is not authenticated.');
        }
    }

    private function applySessionResult(string $operation, ResponseMetadata $metadata): void
    {
        if (1500 === $metadata->resultCode || $metadata->resultCode >= 2500) {
            $this->disconnect();

            return;
        }

        if ('login' === $operation && 1000 === $metadata->resultCode) {
            $this->sessionState?->authenticate();
        }

        $this->closeAfterLogout($operation, $metadata);
    }

    private function closeAfterLogout(string $operation, ResponseMetadata $metadata): void
    {
        if ('logout' !== $operation || !$metadata->isSuccess()) {
            return;
        }

        $this->disconnect();
    }

    private function exchange(?string $xml): string
    {
        try {
            if (null !== $xml) {
                $this->transport->writeFrame($xml);
            }

            return $this->transport->readFrame();
        } catch (\RuntimeException $exception) {
            throw new \RNIDS\Exception\TransportException(
                $exception->getMessage(),
                (int) $exception->getCode(),
                $exception,
            );
        }
    }

    private function disconnect(): void
    {
        $this->sessionState?->disconnect();

        try {
            $this->transport->disconnect();
        } catch (\RuntimeException $exception) {
            throw new \RNIDS\Exception\TransportException(
                $exception->getMessage(),
                (int) $exception->getCode(),
                $exception,
            );
        }
    }

    private function invalidateConnection(): void
    {
        try {
            $this->disconnect();
        } catch (\RNIDS\Exception\TransportException) {
            // Preserve the original command failure when cleanup also fails.
        }
    }
}
