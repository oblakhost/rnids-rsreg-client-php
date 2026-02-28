<?php

declare(strict_types=1);

namespace RNIDS\Host;

use RNIDS\Connection\Transport;
use RNIDS\Host\Dto\HostDeleteRequest;
use RNIDS\Host\Dto\HostInfoRequest;
use RNIDS\Xml\ClTrid\ClTridGenerator;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use RNIDS\Xml\CommandExecutor;
use RNIDS\Xml\Host\HostCheckRequestBuilder;
use RNIDS\Xml\Host\HostCheckResponseParser;
use RNIDS\Xml\Host\HostCreateRequestBuilder;
use RNIDS\Xml\Host\HostCreateResponseParser;
use RNIDS\Xml\Host\HostDeleteRequestBuilder;
use RNIDS\Xml\Host\HostDeleteResponseParser;
use RNIDS\Xml\Host\HostInfoRequestBuilder;
use RNIDS\Xml\Host\HostInfoResponseParser;
use RNIDS\Xml\Host\HostUpdateRequestBuilder;
use RNIDS\Xml\Host\HostUpdateResponseParser;
use RNIDS\Xml\Response\LastResponseMetadata;

/**
 * Provides host (nameserver) command operations.
 */
final class HostService
{
    private CommandExecutor $executor;

    private ClTridGenerator $tridGenerator;

    private HostRequestFactory $requestFactory;

    /**
     * Creates a host service with optional test doubles for execution and DTO mapping.
     */
    public function __construct(
        Transport $transport,
        ?CommandExecutor $executor = null,
        ?ClTridGenerator $tridGenerator = null,
        ?HostRequestFactory $requestFactory = null,
        ?LastResponseMetadata $lastResponseMetadata = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport, null, $lastResponseMetadata);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('HOST');
        $this->requestFactory = $requestFactory ?? new HostRequestFactory();
    }

    /**
     * @param array{names?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return list<array{name: string, available: bool, reason: string|null}>
     */
    public function check(string|array $request): array
    {
        $xml = (new HostCheckRequestBuilder())->build(
            $this->requestFactory->checkFromArray($this->normalizeCheckRequest($request)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new HostCheckResponseParser())->parse($responseXml, $metadata),
        );

        return \array_map(
            static fn(\RNIDS\Host\Dto\HostCheckItem $item): array => [
                'available' => $item->available,
                'name' => $item->name,
                'reason' => $item->reason,
            ],
            $response->items,
        );
    }

    /**
     * @return array{
     *   name: string|null,
     *   roid: string|null,
     *   statuses: list<array{value: string, description: string|null}>,
     *   addresses: list<array{address: string, ipVersion: string}>,
     *   clientId: string|null,
     *   createClientId: string|null,
     *   updateClientId: string|null,
     *   createDate: string|null,
     *   updateDate: string|null,
     *   transferDate: string|null
     * }
     */
    public function info(string $name): array
    {
        $xml = (new HostInfoRequestBuilder())->build(
            new HostInfoRequest($this->requireHostName($name)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new HostInfoResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'addresses' => \array_map(
                static fn(\RNIDS\Host\Dto\HostAddress $address): array => [
                        'address' => $address->address,
                        'ipVersion' => $address->ipVersion,
                    ],
                $response->addresses,
            ),
            'clientId' => $response->clientId,
            'createClientId' => $response->createClientId,
            'createDate' => $response->createDate,
            'name' => $response->name,
            'roid' => $response->roid,
            'statuses' => \array_map(
                static fn(\RNIDS\Host\Dto\HostStatus $status): array => [
                        'description' => $status->description,
                        'value' => $status->value,
                    ],
                $response->statuses,
            ),
            'transferDate' => $response->transferDate,
            'updateClientId' => $response->updateClientId,
            'updateDate' => $response->updateDate,
        ];
    }

    /**
     * @param array{name?: mixed, addresses?: mixed}|non-empty-string $request
     *
     * @return array{name: string|null, createDate: string|null}
     */
    public function create(string|array $request, ?string $ipv4 = null, ?string $ipv6 = null): array
    {
        $normalizedRequest = $this->normalizeCreateRequest($request, $ipv4, $ipv6);

        $xml = (new HostCreateRequestBuilder())->build(
            $this->requestFactory->createFromArray($normalizedRequest),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new HostCreateResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'createDate' => $response->createDate,
            'name' => $response->name,
        ];
    }

    /**
     * @param array{name?: mixed, add?: mixed, remove?: mixed, newName?: mixed} $request
     *
     * @return array{}
     */
    public function update(array $request): array
    {
        $xml = (new HostUpdateRequestBuilder())->build(
            $this->requestFactory->updateFromArray($request),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new HostUpdateResponseParser())->parse($responseXml, $metadata),
        );

        return [];
    }

    /**
     * @return array{}
     */
    public function delete(string $name): array
    {
        $xml = (new HostDeleteRequestBuilder())->build(
            new HostDeleteRequest($this->requireHostName($name)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new HostDeleteResponseParser())->parse($responseXml, $metadata),
        );

        return [];
    }

    private function requireHostName(string $name): string
    {
        if ('' === \trim($name)) {
            throw new \InvalidArgumentException('Host name must be a non-empty string.');
        }

        return $name;
    }

    /**
     * @param array{names?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return array{names: list<string>}
     */
    private function normalizeCheckRequest(string|array $request): array
    {
        if (\is_string($request)) {
            return [ 'names' => [ $this->requireHostName($request) ] ];
        }

        if (isset($request['names'])) {
            return [ 'names' => $request['names'] ];
        }

        if ([] === $request) {
            return [ 'names' => [] ];
        }

        return [
            'names' => \array_values(\array_map(
                fn(mixed $name): string => $this->requireHostNameFromMixed($name),
                $request,
            )),
        ];
    }

    private function requireHostNameFromMixed(mixed $name): string
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException(
                'Host check request key "names" must contain only non-empty strings.',
            );
        }

        return $this->requireHostName($name);
    }

    /**
     * @param array{name?: mixed, addresses?: mixed}|non-empty-string $request
     *
     * @return array{name: string, addresses: list<array{address: string, ipVersion: string}>}|array<string, mixed>
     */
    private function normalizeCreateRequest(string|array $request, ?string $ipv4, ?string $ipv6): array
    {
        if (\is_array($request)) {
            return $request;
        }

        $addresses = $this->buildCreateAddresses($ipv4, $ipv6);

        return [
            'addresses' => $addresses,
            'name' => $this->requireHostName($request),
        ];
    }

    /**
     * @return list<array{address: string, ipVersion: string}>
     */
    private function buildCreateAddresses(?string $ipv4, ?string $ipv6): array
    {
        $addresses = [];

        $this->appendAddress(
            $addresses,
            $ipv4,
            'v4',
            'Host create ipv4 must be a non-empty string when provided.',
        );
        $this->appendAddress(
            $addresses,
            $ipv6,
            'v6',
            'Host create ipv6 must be a non-empty string when provided.',
        );

        return $addresses;
    }

    /**
     * @param list<array{address: string, ipVersion: string}> $addresses
     */
    private function appendAddress(array &$addresses, ?string $address, string $ipVersion, string $error): void
    {
        if (null === $address) {
            return;
        }

        if ('' === \trim($address)) {
            throw new \InvalidArgumentException($error);
        }

        $addresses[] = [
            'address' => $address,
            'ipVersion' => $ipVersion,
        ];
    }
}
