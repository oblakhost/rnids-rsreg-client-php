<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Connection\Transport;
use RNIDS\Domain\Dto\DomainCheckRequest;
use RNIDS\Domain\Dto\DomainDeleteRequest;
use RNIDS\Domain\Dto\DomainInfoRequest;
use RNIDS\Domain\Dto\DomainRenewRequest;
use RNIDS\Domain\Dto\DomainTransferRequest;
use RNIDS\Xml\ClTrid\ClTridGenerator;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use RNIDS\Xml\CommandExecutor;
use RNIDS\Xml\Domain\DomainCheckRequestBuilder;
use RNIDS\Xml\Domain\DomainCheckResponseParser;
use RNIDS\Xml\Domain\DomainDeleteRequestBuilder;
use RNIDS\Xml\Domain\DomainDeleteResponseParser;
use RNIDS\Xml\Domain\DomainInfoRequestBuilder;
use RNIDS\Xml\Domain\DomainInfoResponseParser;
use RNIDS\Xml\Domain\DomainRegisterRequestBuilder;
use RNIDS\Xml\Domain\DomainRegisterResponseParser;
use RNIDS\Xml\Domain\DomainRenewRequestBuilder;
use RNIDS\Xml\Domain\DomainRenewResponseParser;
use RNIDS\Xml\Domain\DomainTransferRequestBuilder;
use RNIDS\Xml\Domain\DomainTransferResponseParser;

/**
 * Provides domain command operations for check, info, and register flows.
 */
final class DomainService
{
    private CommandExecutor $executor;

    private ClTridGenerator $tridGenerator;

    private DomainRegisterRequestFactory $registerRequestFactory;

    /**
     * @param CommandExecutor|null $executor Optional command executor override for tests.
     * @param ClTridGenerator|null $tridGenerator Optional client transaction id generator override.
     * @param DomainRegisterRequestFactory|null $registerRequestFactory Optional request factory override.
     */
    public function __construct(
        Transport $transport,
        ?CommandExecutor $executor = null,
        ?ClTridGenerator $tridGenerator = null,
        ?DomainRegisterRequestFactory $registerRequestFactory = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('DOMAIN');
        $this->registerRequestFactory = $registerRequestFactory ?? new DomainRegisterRequestFactory();
    }

    /**
     * @param array{names?: mixed} $request
     *
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   },
     *   items: list<array{name: string, available: bool, reason: string|null}>
     * }
     */
    public function check(array $request): array
    {
        $xml = (new DomainCheckRequestBuilder())->build(
            new DomainCheckRequest($this->requireNames($request)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainCheckResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'items' => \array_map(
                static fn(\RNIDS\Domain\Dto\DomainCheckItem $item): array => [
                    'available' => $item->available,
                    'name' => $item->name,
                    'reason' => $item->reason,
                ],
                $response->items,
            ),
            'metadata' => [
                'clientTransactionId' => $response->metadata->clientTransactionId,
                'message' => $response->metadata->message,
                'resultCode' => $response->metadata->resultCode,
                'serverTransactionId' => $response->metadata->serverTransactionId,
            ],
        ];
    }

    /**
     * @param array{name?: mixed, hosts?: mixed} $request
     *
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   },
     *   info: array{
     *     name: string|null,
     *     roid: string|null,
     *     statuses: list<array{value: string, description: string|null}>,
     *     registrant: string|null,
     *     contacts: list<array{type: string, handle: string}>,
     *     nameservers: list<array{name: string, addresses: list<string>}>,
     *     clientId: string|null,
     *     createClientId: string|null,
     *     updateClientId: string|null,
     *     createDate: string|null,
     *     updateDate: string|null,
     *     expirationDate: string|null,
     *     extension: array{
     *       isWhoisPrivacy: string|null,
     *       operationMode: string|null,
     *       notifyAdmin: string|null,
     *       dnsSec: string|null,
     *       remark: string|null
     *     }
     *   }
     * }
     */
    public function info(array $request): array
    {
        $xml = (new DomainInfoRequestBuilder())->build(
            new DomainInfoRequest(
                $this->requireName($request),
                $this->optionalHosts($request),
            ),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainInfoResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'info' => [
                'clientId' => $response->clientId,
                'contacts' => \array_map(
                    static fn(\RNIDS\Domain\Dto\DomainInfoContact $contact): array => [
                        'handle' => $contact->handle,
                        'type' => $contact->type,
                    ],
                    $response->contacts,
                ),
                'createClientId' => $response->createClientId,
                'createDate' => $response->createDate,
                'expirationDate' => $response->expirationDate,
                'extension' => [
                    'dnsSec' => $response->extension->dnsSec,
                    'isWhoisPrivacy' => $response->extension->isWhoisPrivacy,
                    'notifyAdmin' => $response->extension->notifyAdmin,
                    'operationMode' => $response->extension->operationMode,
                    'remark' => $response->extension->remark,
                ],
                'name' => $response->name,
                'nameservers' => \array_map(
                    static fn(\RNIDS\Domain\Dto\DomainInfoNameserver $nameserver): array => [
                        'addresses' => $nameserver->addresses,
                        'name' => $nameserver->name,
                    ],
                    $response->nameservers,
                ),
                'registrant' => $response->registrant,
                'roid' => $response->roid,
                'statuses' => \array_map(
                    static fn(\RNIDS\Domain\Dto\DomainInfoStatus $status): array => [
                        'description' => $status->description,
                        'value' => $status->value,
                    ],
                    $response->statuses,
                ),
                'updateClientId' => $response->updateClientId,
                'updateDate' => $response->updateDate,
            ],
            'metadata' => [
                'clientTransactionId' => $response->metadata->clientTransactionId,
                'message' => $response->metadata->message,
                'resultCode' => $response->metadata->resultCode,
                'serverTransactionId' => $response->metadata->serverTransactionId,
            ],
        ];
    }

    /**
     * @param array{
     *   name?: mixed,
     *   period?: mixed,
     *   periodUnit?: mixed,
     *   nameservers?: mixed,
     *   registrant?: mixed,
     *   contacts?: mixed,
     *   authInfo?: mixed,
     *   extension?: mixed
     * } $request
     *
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   },
     *   creation: array{
     *     name: string|null,
     *     createDate: string|null,
     *     expirationDate: string|null
     *   }
     * }
     */
    public function register(array $request): array
    {
        $xml = (new DomainRegisterRequestBuilder())->build(
            $this->registerRequestFactory->fromArray($request),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainRegisterResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'creation' => [
                'createDate' => $response->createDate,
                'expirationDate' => $response->expirationDate,
                'name' => $response->name,
            ],
            'metadata' => [
                'clientTransactionId' => $response->metadata->clientTransactionId,
                'message' => $response->metadata->message,
                'resultCode' => $response->metadata->resultCode,
                'serverTransactionId' => $response->metadata->serverTransactionId,
            ],
        ];
    }

    /**
     * @param array{name?: mixed, currentExpirationDate?: mixed, period?: mixed, periodUnit?: mixed} $request
     *
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   },
     *   renewal: array{
     *     name: string|null,
     *     expirationDate: string|null
     *   }
     * }
     */
    public function renew(array $request): array
    {
        $xml = (new DomainRenewRequestBuilder())->build(
            new DomainRenewRequest(
                $this->requireName($request),
                $this->requireCurrentExpirationDate($request),
                $this->optionalPositiveInt($request, 'period'),
                $this->optionalPeriodUnit($request),
            ),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainRenewResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'metadata' => [
                'clientTransactionId' => $response->metadata->clientTransactionId,
                'message' => $response->metadata->message,
                'resultCode' => $response->metadata->resultCode,
                'serverTransactionId' => $response->metadata->serverTransactionId,
            ],
            'renewal' => [
                'expirationDate' => $response->expirationDate,
                'name' => $response->name,
            ],
        ];
    }

    /**
     * @param array{name?: mixed} $request
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
    public function delete(array $request): array
    {
        $xml = (new DomainDeleteRequestBuilder())->build(
            new DomainDeleteRequest($this->requireName($request)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainDeleteResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'metadata' => [
                'clientTransactionId' => $response->metadata->clientTransactionId,
                'message' => $response->metadata->message,
                'resultCode' => $response->metadata->resultCode,
                'serverTransactionId' => $response->metadata->serverTransactionId,
            ],
        ];
    }

    /**
     * @param array{
     *   operation?: mixed,
     *   name?: mixed,
     *   period?: mixed,
     *   periodUnit?: mixed,
     *   authInfo?: mixed
     * } $request
     *
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   },
     *   transfer: array{
     *     name: string|null,
     *     transferStatus: string|null,
     *     requestClientId: string|null,
     *     requestDate: string|null,
     *     actionClientId: string|null,
     *     actionDate: string|null,
     *     expirationDate: string|null
     *   }
     * }
     */
    public function transfer(array $request): array
    {
        $xml = (new DomainTransferRequestBuilder())->build(
            new DomainTransferRequest(
                $this->requireTransferOperation($request),
                $this->requireName($request),
                $this->optionalPositiveInt($request, 'period'),
                $this->optionalPeriodUnit($request),
                $this->optionalNullableString($request, 'authInfo'),
            ),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainTransferResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'metadata' => [
                'clientTransactionId' => $response->metadata->clientTransactionId,
                'message' => $response->metadata->message,
                'resultCode' => $response->metadata->resultCode,
                'serverTransactionId' => $response->metadata->serverTransactionId,
            ],
            'transfer' => [
                'actionClientId' => $response->actionClientId,
                'actionDate' => $response->actionDate,
                'expirationDate' => $response->expirationDate,
                'name' => $response->name,
                'requestClientId' => $response->requestClientId,
                'requestDate' => $response->requestDate,
                'transferStatus' => $response->transferStatus,
            ],
        ];
    }

    /**
     * @param array{names?: mixed} $request
     *
     * @return list<string>
     */
    private function requireNames(array $request): array
    {
        $names = $request['names'] ?? null;

        $this->assertNamesList($names);

        $result = [];

        foreach ($names as $name) {
            $result[] = $this->requireNameString($name);
        }

        return $result;
    }

    /**
     * @param array{name?: mixed} $request
     */
    private function requireName(array $request): string
    {
        $name = $request['name'] ?? null;

        if (!\is_string($name) || '' === \trim($name)) {
            throw new \InvalidArgumentException('Domain info request key "name" must be a non-empty string.');
        }

        return $name;
    }

    /**
     * @param array{hosts?: mixed} $request
     */
    private function optionalHosts(array $request): string
    {
        $hosts = $request['hosts'] ?? DomainInfoRequest::HOSTS_ALL;

        if (
            !\is_string($hosts)
            || !\in_array(
                $hosts,
                [
                    DomainInfoRequest::HOSTS_ALL,
                    DomainInfoRequest::HOSTS_DELEGATED,
                    DomainInfoRequest::HOSTS_SUBORDINATE,
                    DomainInfoRequest::HOSTS_NONE,
                ],
                true,
            )
        ) {
            throw new \InvalidArgumentException(
                'Domain info request key "hosts" must be one of "all", "del", "sub", or "none".',
            );
        }

        return $hosts;
    }

    private function assertNamesList(mixed $names): void
    {
        if (\is_array($names) && [] !== $names) {
            return;
        }

        throw new \InvalidArgumentException(
            'Domain check request key "names" must be a non-empty list of strings.',
        );
    }

    private function requireNameString(mixed $name): string
    {
        if (!\is_string($name) || '' === \trim($name)) {
            throw new \InvalidArgumentException(
                'Domain check request key "names" must contain only non-empty strings.',
            );
        }

        return $name;
    }

    /**
     * @param array{currentExpirationDate?: mixed} $request
     */
    private function requireCurrentExpirationDate(array $request): string
    {
        $currentExpirationDate = $request['currentExpirationDate'] ?? null;

        if (!\is_string($currentExpirationDate) || '' === \trim($currentExpirationDate)) {
            throw new \InvalidArgumentException(
                'Domain renew request key "currentExpirationDate" must be a non-empty string.',
            );
        }

        return $currentExpirationDate;
    }

    /**
     * @param array{operation?: mixed} $request
     */
    private function requireTransferOperation(array $request): string
    {
        $errorMessage = 'Domain transfer request key "operation" must be one of '
            . '"request", "query", "cancel", "approve", or "reject".';
        $allowedOperations = [ 'request', 'query', 'cancel', 'approve', 'reject' ];

        $operation = $request['operation'] ?? null;

        if (!\is_string($operation)) {
            throw new \InvalidArgumentException($errorMessage);
        }

        if (\in_array($operation, $allowedOperations, true)) {
            return $operation;
        }

        throw new \InvalidArgumentException($errorMessage);
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
                \sprintf('Domain request key "%s" must be a non-empty string when provided.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalPositiveInt(array $request, string $key): ?int
    {
        $value = $request[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_int($value) || $value <= 0) {
            throw new \InvalidArgumentException(
                \sprintf('Domain request key "%s" must be a positive integer when provided.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array{periodUnit?: mixed} $request
     */
    private function optionalPeriodUnit(array $request): string
    {
        $unit = $request['periodUnit'] ?? 'y';

        if (!\is_string($unit) || !\in_array($unit, [ 'y', 'm' ], true)) {
            throw new \InvalidArgumentException(
                'Domain request key "periodUnit" must be either "y" or "m" when provided.',
            );
        }

        return $unit;
    }
}
