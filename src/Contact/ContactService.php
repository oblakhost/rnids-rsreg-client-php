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
    }

    /**
     * @param array{ids?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return list<array{id: string, available: bool, reason: string|null}>
     */
    public function check(string|array $request): array
    {
        $xml = (new ContactCheckRequestBuilder())->build(
            $this->requestFactory->checkFromArray($this->normalizeCheckRequest($request)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new ContactCheckResponseParser())->parse($responseXml, $metadata),
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
     * @param array{
     *   id?: mixed,
     *   postalInfo?: mixed,
     *   voice?: mixed,
     *   fax?: mixed,
     *   email?: mixed,
     *   authInfo?: mixed,
     *   disclose?: mixed,
     *   extension?: mixed
     * } $request
     *
     * @return array{id: string|null, createDate: string|null}
     */
    public function create(array $request): array
    {
        $xml = (new ContactCreateRequestBuilder())->build(
            $this->requestFactory->createFromArray($request),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new ContactCreateResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'createDate' => $response->createDate,
            'id' => $response->id,
        ];
    }

    /**
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
     * }
     */
    public function info(string $id): array
    {
        if ('' === \trim($id)) {
            throw new \InvalidArgumentException('Contact id must be a non-empty string.');
        }

        $xml = (new ContactInfoRequestBuilder())->build(
            new ContactInfoRequest($id),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new ContactInfoResponseParser())->parse($responseXml, $metadata),
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
     * } $request
     *
     * @return array{}
     */
    public function update(array $request): array
    {
        $xml = (new ContactUpdateRequestBuilder())->build(
            $this->requestFactory->updateFromArray($request),
            $this->tridGenerator->nextId(),
        );

        $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new ContactUpdateResponseParser())->parse($responseXml, $metadata),
        );

        return [];
    }

    /**
     * @return array{}
     */
    public function delete(string $id): array
    {
        if ('' === \trim($id)) {
            throw new \InvalidArgumentException('Contact id must be a non-empty string.');
        }

        $xml = (new ContactDeleteRequestBuilder())->build(
            new ContactDeleteRequest($id),
            $this->tridGenerator->nextId(),
        );

        $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new ContactDeleteResponseParser())->parse($responseXml, $metadata),
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
