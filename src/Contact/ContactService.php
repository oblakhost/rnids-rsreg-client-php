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
     */
    public function __construct(
        Transport $transport,
        ?CommandExecutor $executor = null,
        ?ClTridGenerator $tridGenerator = null,
        ?ContactRequestFactory $requestFactory = null,
        ?LastResponseMetadata $lastResponseMetadata = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport, null, $lastResponseMetadata);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('CONTACT');
        $this->requestFactory = $requestFactory ?? new ContactRequestFactory();
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
            $this->requestFactory->checkFromArray($this->normalizeCheckRequest($request)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->checkResponseParser->parse($responseXml, $metadata),
        );

        return \array_map(
            static fn(\RNIDS\Contact\Dto\ContactCheckItem $item): array => [
                'available' => $item->available,
                'id' => $item->id,
                'reason' => $item->reason,
            ],
            $response->items,
        );
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
     * @return array{id: string|null, createDate: string|null} Contact creation result metadata.
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

        return [
            'createDate' => $response->createDate,
            'id' => $response->id,
        ];
    }

    /**
     * Retrieves detailed contact information including RNIDS extension fields.
     *
     * @param string $id Contact identifier to query.
     *
     * @return array{
     *   id: string|null,
     *   roid: string|null,
     *   statuses: list<array{value: string, description: string|null}>,
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
     *   createDate: string|null,
     *   updateDate: string|null,
     *   transferDate: string|null,
     *   disclose: int|null,
     *   extension: array{
     *     ident: string|null,
     *     identDescription: string|null,
     *     identExpiry: string|null,
     *     identKind: string|null,
     *     isLegalEntity: string|null,
     *     vatNo: string|null
     *   }
     * } Parsed contact info response with core and RNIDS-specific values.
     */
    public function info(string $id): array
    {
        if ('' === \trim($id)) {
            throw new \InvalidArgumentException('Contact id must be a non-empty string.');
        }

        $xml = $this->infoRequestBuilder->build(
            new ContactInfoRequest($id),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->infoResponseParser->parse($responseXml, $metadata),
        );

        $postalInfo = null;

        if (null !== $response->postalInfo) {
            $postalInfo = [
                'address' => [
                    'city' => $response->postalInfo->address->city,
                    'countryCode' => $response->postalInfo->address->countryCode,
                    'postalCode' => $response->postalInfo->address->postalCode,
                    'province' => $response->postalInfo->address->province,
                    'streets' => $response->postalInfo->address->streets,
                ],
                'name' => $response->postalInfo->name,
                'organization' => $response->postalInfo->organization,
                'type' => $response->postalInfo->type,
            ];
        }

        return [
            'clientId' => $response->clientId,
            'createClientId' => $response->createClientId,
            'createDate' => $response->createDate,
            'disclose' => $response->disclose,
            'email' => $response->email,
            'extension' => [
                'ident' => $response->extension->ident,
                'identDescription' => $response->extension->identDescription,
                'identExpiry' => $response->extension->identExpiry,
                'identKind' => $response->extension->identKind,
                'isLegalEntity' => $response->extension->isLegalEntity,
                'vatNo' => $response->extension->vatNo,
            ],
            'fax' => $response->fax,
            'id' => $response->id,
            'postalInfo' => $postalInfo,
            'roid' => $response->roid,
            'statuses' => \array_map(
                static fn(\RNIDS\Contact\Dto\ContactStatus $status): array => [
                    'description' => $status->description,
                    'value' => $status->value,
                ],
                $response->statuses,
            ),
            'transferDate' => $response->transferDate,
            'updateClientId' => $response->updateClientId,
            'updateDate' => $response->updateDate,
            'voice' => $response->voice,
        ];
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

        return [];
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
        if ('' === \trim($id)) {
            throw new \InvalidArgumentException('Contact id must be a non-empty string.');
        }

        $xml = $this->deleteRequestBuilder->build(
            new ContactDeleteRequest($id),
            $this->tridGenerator->nextId(),
        );

        $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->deleteResponseParser->parse($responseXml, $metadata),
        );

        return [];
    }

    /**
     * @param array{ids?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return array{ids: list<string>}
     */
    private function normalizeCheckRequest(string|array $request): array
    {
        if (\is_string($request)) {
            return [ 'ids' => [ $this->requireCheckId($request) ] ];
        }

        if (isset($request['ids'])) {
            return [ 'ids' => $request['ids'] ];
        }

        return [ 'ids' => $this->normalizeCheckIdsList($request) ];
    }

    private function requireCheckId(string $id): string
    {
        if ('' === \trim($id)) {
            throw new \InvalidArgumentException('Contact check id must be a non-empty string.');
        }

        return $id;
    }

    /**
     * @param list<mixed> $request
     *
     * @return list<string>
     */
    private function normalizeCheckIdsList(array $request): array
    {
        if ([] === $request) {
            return [];
        }

        return [
            ...\array_values(\array_map(
                static function (mixed $value): string {
                    if (!\is_string($value) || '' === \trim($value)) {
                        throw new \InvalidArgumentException(
                            'Contact check request list must contain only non-empty strings.',
                        );
                    }

                    return $value;
                },
                $request,
            )),
        ];
    }
}
