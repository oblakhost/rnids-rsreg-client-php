<?php

declare(strict_types=1);

namespace RNIDS\Session;

use RNIDS\Connection\Transport;
use RNIDS\Session\Dto\LoginRequest;
use RNIDS\Session\Dto\PollRequest;
use RNIDS\Xml\ClTrid\ClTridGenerator;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use RNIDS\Xml\CommandExecutor;
use RNIDS\Xml\Session\HelloRequestBuilder;
use RNIDS\Xml\Session\HelloResponseParser;
use RNIDS\Xml\Session\LoginRequestBuilder;
use RNIDS\Xml\Session\LoginResponseParser;
use RNIDS\Xml\Session\LogoutRequestBuilder;
use RNIDS\Xml\Session\LogoutResponseParser;
use RNIDS\Xml\Session\PollRequestBuilder;
use RNIDS\Xml\Session\PollResponseParser;

final class SessionService
{
    private CommandExecutor $executor;

    private ClTridGenerator $tridGenerator;

    /**
     * @param CommandExecutor|null $executor Optional command executor override for tests.
     * @param ClTridGenerator|null $tridGenerator Optional client transaction id generator override.
     */
    public function __construct(
        Transport $transport,
        ?CommandExecutor $executor = null,
        ?ClTridGenerator $tridGenerator = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('SESSION');
    }

    /**
     * @param array{
     *   clientId: non-empty-string,
     *   password: non-empty-string,
     *   version?: non-empty-string,
     *   language?: non-empty-string,
     *   objectUris?: list<non-empty-string>,
     *   extensionUris?: list<non-empty-string>
     * } $request
     *
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   }
     * }
     */
    public function login(array $request): array
    {
        $xml = (new LoginRequestBuilder())->build(
            new LoginRequest(
                clientId: $this->requireString($request, 'clientId'),
                password: $this->requireString($request, 'password'),
                version: $this->optionalString($request, 'version', '1.0'),
                language: $this->optionalString($request, 'language', 'en'),
                objectUris: $this->optionalStringList($request, 'objectUris'),
                extensionUris: $this->optionalStringList($request, 'extensionUris'),
            ),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new LoginResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'metadata' => $this->metadataToArray($response->metadata),
        ];
    }

    /**
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   },
     *   greeting: array{
     *     extensionUris: list<string>,
     *     languages: list<string>,
     *     objectUris: list<string>,
     *     serverDate: string|null,
     *     serverId: string|null,
     *     versions: list<string>
     *   }
     * }
     */
    public function hello(): array
    {
        $response = $this->executor->execute(
            (new HelloRequestBuilder())->build(),
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new HelloResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'greeting' => [
                'extensionUris' => $response->extensionUris,
                'languages' => $response->languages,
                'objectUris' => $response->objectUris,
                'serverDate' => $response->serverDate,
                'serverId' => $response->serverId,
                'versions' => $response->versions,
            ],
            'metadata' => $this->metadataToArray($response->metadata),
        ];
    }

    /**
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   }
     * }
     */
    public function logout(): array
    {
        $xml = (new LogoutRequestBuilder())->build($this->tridGenerator->nextId());

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new LogoutResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'metadata' => $this->metadataToArray($response->metadata),
        ];
    }

    /**
     * @param array{messageId?: mixed, operation?: mixed} $request
     *
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   },
     *   queue: array{
     *     count: int|null,
     *     message: string|null,
     *     messageId: string|null,
     *     queueDate: string|null
     *   }
     * }
     */
    public function poll(array $request = []): array
    {
        $pollRequest = $this->buildPollRequest($request);
        $xml = (new PollRequestBuilder())->build($pollRequest, $this->tridGenerator->nextId());

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new PollResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'metadata' => $this->metadataToArray($response->metadata),
            'queue' => [
                'count' => $response->queueCount,
                'message' => $response->message,
                'messageId' => $response->messageId,
                'queueDate' => $response->queueDate,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $request
     */
    private function requireString(array $request, string $key): string
    {
        $value = $request[$key] ?? null;

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Session request key "%s" must be a non-empty string.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalString(array $request, string $key, string $default): string
    {
        $value = $request[$key] ?? null;

        if (null === $value) {
            return $default;
        }

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Session request key "%s" must be a non-empty string.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return list<string>
     */
    private function optionalStringList(array $request, string $key): array
    {
        $value = $request[$key] ?? null;

        if (null === $value) {
            return [];
        }

        if (!\is_array($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Session request key "%s" must be a list of strings.', $key),
            );
        }

        $result = [];

        foreach ($value as $item) {
            if (!\is_string($item) || '' === \trim($item)) {
                throw new \InvalidArgumentException(
                    \sprintf('Session request key "%s" must contain only non-empty strings.', $key),
                );
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array{messageId?: mixed, operation?: mixed} $request
     */
    private function buildPollRequest(array $request): PollRequest
    {
        $operation = $this->optionalPollOperation($request);

        if ('ack' === $operation) {
            return new PollRequest($operation, $this->requireString($request, 'messageId'));
        }

        return new PollRequest($operation, $this->optionalNullableString($request, 'messageId'));
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalNullableString(array $request, string $key): ?string
    {
        $value = $request[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Session request key "%s" must be a non-empty string when provided.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array{operation?: mixed} $request
     */
    private function optionalPollOperation(array $request): string
    {
        $operation = $request['operation'] ?? 'req';

        if (!\is_string($operation) || !\in_array($operation, [ 'req', 'ack' ], true)) {
            throw new \InvalidArgumentException(
                'Session poll request key "operation" must be either "req" or "ack".',
            );
        }

        return $operation;
    }

    /**
     * @return array{
     *   clientTransactionId: string|null,
     *   message: string,
     *   resultCode: int,
     *   serverTransactionId: string|null
     * }
     */
    private function metadataToArray(\RNIDS\Xml\Response\ResponseMetadata $metadata): array
    {
        return [
            'clientTransactionId' => $metadata->clientTransactionId,
            'message' => $metadata->message,
            'resultCode' => $metadata->resultCode,
            'serverTransactionId' => $metadata->serverTransactionId,
        ];
    }
}
