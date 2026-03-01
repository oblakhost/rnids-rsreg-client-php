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

    private HostInputNormalizer $inputNormalizer;

    private HostResponseMapper $responseMapper;

    private HostCheckRequestBuilder $checkRequestBuilder;

    private HostCheckResponseParser $checkResponseParser;

    private HostInfoRequestBuilder $infoRequestBuilder;

    private HostInfoResponseParser $infoResponseParser;

    private HostCreateRequestBuilder $createRequestBuilder;

    private HostCreateResponseParser $createResponseParser;

    private HostUpdateRequestBuilder $updateRequestBuilder;

    private HostUpdateResponseParser $updateResponseParser;

    private HostDeleteRequestBuilder $deleteRequestBuilder;

    private HostDeleteResponseParser $deleteResponseParser;

    /**
     * Creates a host service with optional test doubles for execution and DTO mapping.
     *
     * @param Transport $transport Connected transport used to send and receive EPP frames.
     * @param CommandExecutor|null $executor Optional command executor override for tests.
     * @param ClTridGenerator|null $tridGenerator Optional client transaction id generator override.
     * @param HostRequestFactory|null $requestFactory Optional request DTO factory override.
     * @param LastResponseMetadata|null $lastResponseMetadata Optional shared holder for last parsed response metadata.
     * @param HostInputNormalizer|null $inputNormalizer Optional input normalizer override.
     * @param HostResponseMapper|null $responseMapper Optional response mapper override.
     */
    public function __construct(
        Transport $transport,
        ?CommandExecutor $executor = null,
        ?ClTridGenerator $tridGenerator = null,
        ?HostRequestFactory $requestFactory = null,
        ?LastResponseMetadata $lastResponseMetadata = null,
        ?HostInputNormalizer $inputNormalizer = null,
        ?HostResponseMapper $responseMapper = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport, null, $lastResponseMetadata);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('HOST');
        $this->requestFactory = $requestFactory ?? new HostRequestFactory();
        $this->inputNormalizer = $inputNormalizer ?? new HostInputNormalizer();
        $this->responseMapper = $responseMapper ?? new HostResponseMapper();
        $this->checkRequestBuilder = new HostCheckRequestBuilder();
        $this->checkResponseParser = new HostCheckResponseParser();
        $this->infoRequestBuilder = new HostInfoRequestBuilder();
        $this->infoResponseParser = new HostInfoResponseParser();
        $this->createRequestBuilder = new HostCreateRequestBuilder();
        $this->createResponseParser = new HostCreateResponseParser();
        $this->updateRequestBuilder = new HostUpdateRequestBuilder();
        $this->updateResponseParser = new HostUpdateResponseParser();
        $this->deleteRequestBuilder = new HostDeleteRequestBuilder();
        $this->deleteResponseParser = new HostDeleteResponseParser();
    }

    /**
     * Checks one or more host names for availability.
     *
     * @param array{names?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return list<array{name: string, available: bool, reason: string|null}>
     *   Availability data for each requested host name.
     */
    public function check(string|array $request): array
    {
        $xml = $this->checkRequestBuilder->build(
            $this->requestFactory->checkFromArray($this->inputNormalizer->normalizeCheckRequest($request)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->checkResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapCheckResponse($response);
    }

    /**
     * Retrieves detailed host information.
     *
     * @param string $name Host name to query.
     *
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
     * } Parsed host info response including statuses and host addresses.
     */
    public function info(string $name): array
    {
        $xml = $this->infoRequestBuilder->build(
            new HostInfoRequest($this->inputNormalizer->requireHostName($name)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->infoResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapInfoResponse($response);
    }

    /**
     * Creates a host object using full payload or simplified name/IP arguments.
     *
     * @param array{name?: mixed, addresses?: mixed}|non-empty-string $request
     * @param string|null $ipv4 Optional IPv4 address for the simplified API variant.
     * @param string|null $ipv6 Optional IPv6 address for the simplified API variant.
     *
     * @return array{name: string|null, createDate: string|null} Host creation result metadata.
     */
    public function create(string|array $request, ?string $ipv4 = null, ?string $ipv6 = null): array
    {
        $normalizedRequest = $this->inputNormalizer->normalizeCreateRequest($request, $ipv4, $ipv6);

        $xml = $this->createRequestBuilder->build(
            $this->requestFactory->createFromArray($normalizedRequest),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->createResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapCreateResponse($response);
    }

    /**
     * Updates an existing host object.
     *
     * @param array{name?: mixed, add?: mixed, remove?: mixed, newName?: mixed} $request
     *
     * @return array{} Empty array on successful host update command completion.
     */
    public function update(array $request): array
    {
        $xml = $this->updateRequestBuilder->build(
            $this->requestFactory->updateFromArray($request),
            $this->tridGenerator->nextId(),
        );

        $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->updateResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapEmptyResponse();
    }

    /**
     * Deletes a host object by name.
     *
     * @param string $name Host name to delete.
     *
     * @return array{} Empty array on successful host delete command completion.
     */
    public function delete(string $name): array
    {
        $xml = $this->deleteRequestBuilder->build(
            new HostDeleteRequest($this->inputNormalizer->requireHostName($name)),
            $this->tridGenerator->nextId(),
        );

        $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->deleteResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapEmptyResponse();
    }
}
