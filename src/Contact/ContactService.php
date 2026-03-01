<?php

declare(strict_types=1);

namespace RNIDS\Contact;

use RNIDS\Connection\Transport;
use RNIDS\Contact\Dto\ContactDeleteRequest;
use RNIDS\Contact\Dto\ContactInfoRequest;
use RNIDS\Xml\ClTrid\ClTridGenerator;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use RNIDS\Xml\CommandExecutor;
use RNIDS\Xml\Contact\ContactCheckRequestBuilder;
use RNIDS\Xml\Contact\ContactCheckResponseParser;
use RNIDS\Xml\Contact\ContactCreateRequestBuilder;
use RNIDS\Xml\Contact\ContactCreateResponseParser;
use RNIDS\Xml\Contact\ContactDeleteRequestBuilder;
use RNIDS\Xml\Contact\ContactDeleteResponseParser;
use RNIDS\Xml\Contact\ContactInfoRequestBuilder;
use RNIDS\Xml\Contact\ContactInfoResponseParser;
use RNIDS\Xml\Contact\ContactUpdateRequestBuilder;
use RNIDS\Xml\Contact\ContactUpdateResponseParser;
use RNIDS\Xml\Response\LastResponseMetadata;

/**
 * Provides contact command operations.
 */
final class ContactService
{
    private CommandExecutor $executor;

    private ClTridGenerator $tridGenerator;

    private ContactRequestFactory $requestFactory;

    private ContactInputNormalizer $inputNormalizer;

    private ContactResponseMapper $responseMapper;

    private ContactCheckRequestBuilder $checkRequestBuilder;

    private ContactCheckResponseParser $checkResponseParser;

    private ContactCreateRequestBuilder $createRequestBuilder;

    private ContactCreateResponseParser $createResponseParser;

    private ContactInfoRequestBuilder $infoRequestBuilder;

    private ContactInfoResponseParser $infoResponseParser;

    private ContactUpdateRequestBuilder $updateRequestBuilder;

    private ContactUpdateResponseParser $updateResponseParser;

    private ContactDeleteRequestBuilder $deleteRequestBuilder;

    private ContactDeleteResponseParser $deleteResponseParser;

    /**
     * Creates a contact service for RNIDS contact lifecycle operations.
     *
     * @param Transport $transport Connected transport used to send and receive EPP frames.
     * @param CommandExecutor|null $executor Optional command executor override for tests.
     * @param ClTridGenerator|null $tridGenerator Optional client transaction id generator override.
     * @param ContactRequestFactory|null $requestFactory Optional request DTO factory override.
     * @param LastResponseMetadata|null $lastResponseMetadata Optional shared holder for last parsed response metadata.
     * @param ContactInputNormalizer|null $inputNormalizer Optional input normalizer override.
     * @param ContactResponseMapper|null $responseMapper Optional response mapper override.
     */
    public function __construct(
        Transport $transport,
        ?CommandExecutor $executor = null,
        ?ClTridGenerator $tridGenerator = null,
        ?ContactRequestFactory $requestFactory = null,
        ?LastResponseMetadata $lastResponseMetadata = null,
        ?ContactInputNormalizer $inputNormalizer = null,
        ?ContactResponseMapper $responseMapper = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport, null, $lastResponseMetadata);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('CONTACT');
        $this->requestFactory = $requestFactory ?? new ContactRequestFactory();
        $this->inputNormalizer = $inputNormalizer ?? new ContactInputNormalizer();
        $this->responseMapper = $responseMapper ?? new ContactResponseMapper();
        $this->checkRequestBuilder = new ContactCheckRequestBuilder();
        $this->checkResponseParser = new ContactCheckResponseParser();
        $this->createRequestBuilder = new ContactCreateRequestBuilder();
        $this->createResponseParser = new ContactCreateResponseParser();
        $this->infoRequestBuilder = new ContactInfoRequestBuilder();
        $this->infoResponseParser = new ContactInfoResponseParser();
        $this->updateRequestBuilder = new ContactUpdateRequestBuilder();
        $this->updateResponseParser = new ContactUpdateResponseParser();
        $this->deleteRequestBuilder = new ContactDeleteRequestBuilder();
        $this->deleteResponseParser = new ContactDeleteResponseParser();
    }

    /**
     * Checks one or more contact identifiers for availability.
     *
     * @param array{ids?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return list<array{id: string, available: bool, reason: string|null}>
     *   Availability data for each requested contact identifier.
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
     * Creates a new contact object.
     *
     * @param array{
     *   id?: mixed,
     *   postalInfo?: mixed,
     *   voice?: mixed,
     *   fax?: mixed,
     *   email?: mixed,
     *   authInfo?: mixed,
     *   disclose?: mixed,
     *   extension?: mixed
     * } $request Contact create payload containing required identity/address fields and optional extension data.
     *
     * @return array{id: string|null, createDate: \DateTimeImmutable|null} Contact creation result metadata.
     */
    public function create(array $request): array
    {
        $xml = $this->createRequestBuilder->build(
            $this->requestFactory->createFromArray($request),
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
     * Retrieves detailed contact information including RNIDS extension fields.
     *
     * @param string $id Contact identifier to query.
     *
     * @return array{
     *   id: string|null,
     *   roid: string|null,
     *   statuses: list<string>,
     *   postalInfo: array{
     *     type: string,
     *     name: string,
     *     organization: string|null,
     *     address: array{
     *       streets: list<string>,
     *       city: string,
     *       countryCode: string,
     *       province: string|null,
     *       postalCode: string|null
     *     }
     *   }|null,
     *   voice: string|null,
     *   fax: string|null,
     *   email: string|null,
     *   clientId: string|null,
     *   createClientId: string|null,
     *   updateClientId: string|null,
     *   createDate: \DateTimeImmutable|null,
     *   updateDate: \DateTimeImmutable|null,
     *   transferDate: \DateTimeImmutable|null,
     *   disclose: int|null,
     *   ident: string|null,
     *   identDescription: string|null,
     *   identExpiry: string|null,
     *   identKind: string|null,
     *   legalEntity: bool,
     *   vatNo: string|null
     * } Parsed contact info response with core and RNIDS-specific values.
     */
    public function info(string $id): array
    {
        $xml = $this->infoRequestBuilder->build(
            new ContactInfoRequest($this->inputNormalizer->requireContactId($id)),
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
     * Updates an existing contact object.
     *
     * @param array{
     *   id?: mixed,
     *   addStatuses?: mixed,
     *   removeStatuses?: mixed,
     *   postalInfo?: mixed,
     *   voice?: mixed,
     *   fax?: mixed,
     *   email?: mixed,
     *   authInfo?: mixed,
     *   disclose?: mixed,
     *   extension?: mixed
     * } $request Contact update payload describing add/remove/change operations.
     *
     * @return array{} Empty array on successful contact update command completion.
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
     * Deletes a contact object by identifier.
     *
     * @param string $id Contact identifier to delete.
     *
     * @return array{} Empty array on successful contact delete command completion.
     */
    public function delete(string $id): array
    {
        $xml = $this->deleteRequestBuilder->build(
            new ContactDeleteRequest($this->inputNormalizer->requireContactId($id)),
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
