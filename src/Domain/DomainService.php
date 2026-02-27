<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Connection\Transport;
use RNIDS\Domain\Dto\DomainCheckRequest;
use RNIDS\Domain\Dto\DomainInfoRequest;
use RNIDS\Xml\ClTrid\ClTridGenerator;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use RNIDS\Xml\CommandExecutor;
use RNIDS\Xml\Domain\DomainCheckRequestBuilder;
use RNIDS\Xml\Domain\DomainCheckResponseParser;
use RNIDS\Xml\Domain\DomainInfoRequestBuilder;
use RNIDS\Xml\Domain\DomainInfoResponseParser;
use RNIDS\Xml\Domain\DomainRegisterRequestBuilder;
use RNIDS\Xml\Domain\DomainRegisterResponseParser;

/**
 * Provides domain command operations for check, info, and register flows.
 */
final class DomainService
{
    private CommandExecutor $executor;

    private ClTridGenerator $tridGenerator;

    private DomainRegisterRequestFactory $registerRequestFactory;

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
}
