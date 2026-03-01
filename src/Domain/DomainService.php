<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Connection\Transport;
use RNIDS\Domain\Dto\DomainCheckRequest;
use RNIDS\Domain\Dto\DomainDeleteRequest;
use RNIDS\Domain\Dto\DomainInfoRequest;
use RNIDS\Domain\Dto\DomainRegisterContact;
use RNIDS\Domain\Dto\DomainRenewRequest;
use RNIDS\Domain\Dto\DomainTransferRequest;
use RNIDS\Domain\Dto\DomainUpdateRequest;
use RNIDS\Domain\Dto\DomainUpdateSection;
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
use RNIDS\Xml\Domain\DomainUpdateRequestBuilder;
use RNIDS\Xml\Domain\DomainUpdateResponseParser;
use RNIDS\Xml\Response\LastResponseMetadata;

/**
 * Provides domain command operations for check, info, and register flows.
 */
final class DomainService
{
    private CommandExecutor $executor;

    private ClTridGenerator $tridGenerator;

    private DomainRegisterRequestFactory $registerRequestFactory;

    private DomainInputNormalizer $inputNormalizer;

    private DomainResponseMapper $responseMapper;

    private DomainCheckRequestBuilder $checkRequestBuilder;

    private DomainCheckResponseParser $checkResponseParser;

    private DomainInfoRequestBuilder $infoRequestBuilder;

    private DomainInfoResponseParser $infoResponseParser;

    private DomainRegisterRequestBuilder $registerRequestBuilder;

    private DomainRegisterResponseParser $registerResponseParser;

    private DomainRenewRequestBuilder $renewRequestBuilder;

    private DomainRenewResponseParser $renewResponseParser;

    private DomainDeleteRequestBuilder $deleteRequestBuilder;

    private DomainDeleteResponseParser $deleteResponseParser;

    private DomainTransferRequestBuilder $transferRequestBuilder;

    private DomainTransferResponseParser $transferResponseParser;

    private DomainUpdateRequestBuilder $updateRequestBuilder;

    private DomainUpdateResponseParser $updateResponseParser;

    /**
     * Creates a domain service for RNIDS domain lifecycle operations.
     *
     * @param Transport $transport Connected transport used to send and receive EPP frames.
     * @param CommandExecutor|null $executor Optional command executor override for tests.
     * @param ClTridGenerator|null $tridGenerator Optional client transaction id generator override.
     * @param DomainRegisterRequestFactory|null $registerRequestFactory Optional request factory override.
     * @param LastResponseMetadata|null $lastResponseMetadata Optional shared holder for last parsed response metadata.
     * @param DomainInputNormalizer|null $inputNormalizer Optional request input normalizer override.
     * @param DomainResponseMapper|null $responseMapper Optional response mapper override.
     */
    public function __construct(
        Transport $transport,
        ?CommandExecutor $executor = null,
        ?ClTridGenerator $tridGenerator = null,
        ?DomainRegisterRequestFactory $registerRequestFactory = null,
        ?LastResponseMetadata $lastResponseMetadata = null,
        ?DomainInputNormalizer $inputNormalizer = null,
        ?DomainResponseMapper $responseMapper = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport, null, $lastResponseMetadata);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('DOMAIN');
        $this->registerRequestFactory = $registerRequestFactory ?? new DomainRegisterRequestFactory();
        $this->inputNormalizer = $inputNormalizer ?? new DomainInputNormalizer(
            new DomainNameserverNormalizer(),
        );
        $this->responseMapper = $responseMapper ?? new DomainResponseMapper();
        $this->checkRequestBuilder = new DomainCheckRequestBuilder();
        $this->checkResponseParser = new DomainCheckResponseParser();
        $this->infoRequestBuilder = new DomainInfoRequestBuilder();
        $this->infoResponseParser = new DomainInfoResponseParser();
        $this->registerRequestBuilder = new DomainRegisterRequestBuilder();
        $this->registerResponseParser = new DomainRegisterResponseParser();
        $this->renewRequestBuilder = new DomainRenewRequestBuilder();
        $this->renewResponseParser = new DomainRenewResponseParser();
        $this->deleteRequestBuilder = new DomainDeleteRequestBuilder();
        $this->deleteResponseParser = new DomainDeleteResponseParser();
        $this->transferRequestBuilder = new DomainTransferRequestBuilder();
        $this->transferResponseParser = new DomainTransferResponseParser();
        $this->updateRequestBuilder = new DomainUpdateRequestBuilder();
        $this->updateResponseParser = new DomainUpdateResponseParser();
    }

    /**
     * @param array{names?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return list<array{name: string, available: bool, reason: string|null}>
     */
    public function check(string|array $request): array
    {
        $xml = $this->checkRequestBuilder->build(
            new DomainCheckRequest($this->inputNormalizer->normalizeCheckNames($request)),
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
     * @return array{
     *   name: string|null,
     *   roid: string|null,
     *   statuses: list<array{value: string, description: string|null}>,
     *   registrant: string|null,
     *   contacts: list<array{type: string, handle: string}>,
     *   nameservers: list<array{name: string, addresses: list<string>}>,
     *   clientId: string|null,
     *   createClientId: string|null,
     *   updateClientId: string|null,
     *   createDate: string|null,
     *   updateDate: string|null,
     *   expirationDate: string|null,
     *   extension: array{
     *     isWhoisPrivacy: string|null,
     *     operationMode: string|null,
     *     notifyAdmin: string|null,
     *     dnsSec: string|null,
     *     remark: string|null
     *   }
     * }
     */
    public function info(string $name, ?string $hosts = null): array
    {
        $xml = $this->infoRequestBuilder->build(
            new DomainInfoRequest(
                $this->inputNormalizer->requireDomainName($name),
                $this->inputNormalizer->optionalHosts($hosts),
            ),
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
     * @param array{
     *   name?: mixed,
     *   period?: mixed,
     *   periodUnit?: mixed,
     *   nameservers?: mixed,
     *   registrant?: mixed,
     *   contacts?: mixed,
     *   authInfo?: mixed,
     *   extension?: mixed
     * }|non-empty-string $request
     * @param non-empty-string|null $registrant
     * @param non-empty-string|null $adminContact
     * @param non-empty-string|null $techContact
     * @param non-empty-string|array<int, mixed>|null $nameservers
     * @param int|null $years
     * @param array{
     *   isWhoisPrivacy?: mixed,
     *   operationMode?: mixed,
     *   notifyAdmin?: mixed,
     *   dnsSec?: mixed,
     *   remark?: mixed
     * }|null $extension
     *
     * @return array{name: string|null, createDate: string|null, expirationDate: string|null}
     */
    public function register(
        string|array $request,
        ?string $registrant = null,
        ?string $adminContact = null,
        ?string $techContact = null,
        string|array|null $nameservers = null,
        ?int $years = 1,
        ?string $authInfo = null,
        ?array $extension = null,
    ): array {
        $normalizedRequest = $this->inputNormalizer->normalizeRegisterRequest(
            $request,
            $registrant,
            $adminContact,
            $techContact,
            $nameservers,
            $years,
            $authInfo,
            $extension,
        );

        $xml = $this->registerRequestBuilder->build(
            $this->registerRequestFactory->fromArray($normalizedRequest),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->registerResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapRegisterResponse($response);
    }

    /**
     * @param array{
     *   name?: mixed,
     *   currentExpirationDate?: mixed,
     *   period?: mixed,
     *   periodUnit?: mixed
     * }|non-empty-string $request
     * @param int|null $years Renewal period used by the simplified API variant.
     *
     * @return array{name: string|null, expirationDate: string|null}
     */
    public function renew(string|array $request, ?int $years = null): array
    {
        $normalizedRequest = $this->inputNormalizer->normalizeRenewRequest(
            $request,
            $years,
            fn(string $name): string => $this->resolveCurrentExpirationDate($name),
        );

        $xml = $this->renewRequestBuilder->build(
            new DomainRenewRequest(
                $this->inputNormalizer->requireName($normalizedRequest),
                $this->inputNormalizer->requireCurrentExpirationDate($normalizedRequest),
                $this->inputNormalizer->optionalPositiveInt($normalizedRequest, 'period'),
                $this->inputNormalizer->optionalPeriodUnit($normalizedRequest),
            ),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->renewResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapRenewResponse($response);
    }

    /**
     * @return array{} Empty array on successful domain delete command completion.
     */
    public function delete(string $name): array
    {
        $xml = $this->deleteRequestBuilder->build(
            new DomainDeleteRequest($this->inputNormalizer->requireDomainName($name)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->deleteResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapDeleteResponse();
    }

    /**
     * @param array{
     *   name?: mixed,
     *   add?: mixed,
     *   remove?: mixed,
     *   registrant?: mixed,
     *   authInfo?: mixed
     * } $request
     *
     * @return array{} Empty array on successful domain update command completion.
     */
    public function update(array $request): array
    {
        $xml = $this->updateRequestBuilder->build(
            $this->buildUpdateRequest($request),
            $this->tridGenerator->nextId(),
        );

        $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->updateResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapDeleteResponse();
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
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: string|null,
     *   actionClientId: string|null,
     *   actionDate: string|null,
     *   expirationDate: string|null
     * }
     */
    public function transfer(array $request): array
    {
        $xml = $this->transferRequestBuilder->build(
            new DomainTransferRequest(
                $this->inputNormalizer->requireTransferOperation($request),
                $this->inputNormalizer->requireName($request),
                $this->inputNormalizer->optionalPositiveInt($request, 'period'),
                $this->inputNormalizer->optionalPeriodUnit($request),
                $this->inputNormalizer->optionalNullableString($request, 'authInfo'),
            ),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->transferResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapTransferResponse($response);
    }

    private function resolveCurrentExpirationDate(string $name): string
    {
        $info = $this->info($name);
        $expirationDate = $info['expirationDate'] ?? null;

        if (!\is_string($expirationDate) || '' === \trim($expirationDate)) {
            throw new \InvalidArgumentException(
                'Unable to resolve current expiration date for simplified domain renew API.',
            );
        }

        return $expirationDate;
    }

    /**
     * @param array{
     *   name?: mixed,
     *   add?: mixed,
     *   remove?: mixed,
     *   registrant?: mixed,
     *   authInfo?: mixed
     * } $request
     */
    private function buildUpdateRequest(array $request): DomainUpdateRequest
    {
        $name = $this->inputNormalizer->requireName($request);
        $add = $this->parseUpdateSection($request['add'] ?? null, 'add');
        $remove = $this->parseUpdateSection($request['remove'] ?? null, 'remove');
        $registrant = $this->inputNormalizer->optionalNullableString($request, 'registrant');
        $authInfo = $this->inputNormalizer->optionalNullableString($request, 'authInfo');

        if (null === $add && null === $remove && null === $registrant && null === $authInfo) {
            throw new \InvalidArgumentException(
                'Domain update request must include at least one of "add", "remove", "registrant", or "authInfo".',
            );
        }

        return new DomainUpdateRequest($name, $add, $remove, $registrant, $authInfo);
    }

    private function parseUpdateSection(mixed $section, string $key): ?DomainUpdateSection
    {
        if (null === $section) {
            return null;
        }

        if (!\is_array($section)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain update request key "%s" must be an array when provided.', $key),
            );
        }

        $contacts = $this->parseUpdateContacts($section, $key);
        $statuses = $this->parseUpdateStatuses($section, $key);

        if ([] === $contacts && [] === $statuses) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain update request section "%s" must include at least one of "contacts" or "statuses".',
                    $key,
                ),
            );
        }

        return new DomainUpdateSection($contacts, $statuses);
    }

    /**
     * @param array<string, mixed> $section
     *
     * @return list<DomainRegisterContact>
     */
    private function parseUpdateContacts(array $section, string $key): array
    {
        $contacts = $section['contacts'] ?? [];

        if (!\is_array($contacts)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain update request section "%s" key "contacts" must be a list when provided.',
                    $key,
                ),
            );
        }

        return \array_values(\array_map(
            fn(mixed $contact, int $index): DomainRegisterContact => $this->parseUpdateContact(
                $contact,
                $key,
                $index,
            ),
            $contacts,
            \array_keys($contacts),
        ));
    }

    private function parseUpdateContact(mixed $contact, string $key, int $index): DomainRegisterContact
    {
        if (!\is_array($contact)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain update request section "%s" contact at index %d must be an array.',
                    $key,
                    $index,
                ),
            );
        }

        $type = $contact['type'] ?? null;
        if (!\is_string($type) || !\in_array($type, [ 'admin', 'tech', 'billing' ], true)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain update request section "%s" contact at index %d has invalid "type"'
                    . ' (allowed: admin, tech, billing).',
                    $key,
                    $index,
                ),
            );
        }

        $handle = $contact['handle'] ?? null;
        if (!\is_string($handle) || '' === \trim($handle)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain update request section "%s" contact at index %d must include non-empty "handle".',
                    $key,
                    $index,
                ),
            );
        }

        return new DomainRegisterContact($type, $handle);
    }

    /**
     * @param array<string, mixed> $section
     *
     * @return list<string>
     */
    private function parseUpdateStatuses(array $section, string $key): array
    {
        $statuses = $section['statuses'] ?? [];

        if (!\is_array($statuses)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain update request section "%s" key "statuses" must be a list when provided.',
                    $key,
                ),
            );
        }

        return \array_values(\array_map(
            fn(mixed $status, int $index): string => $this->parseUpdateStatus($status, $key, $index),
            $statuses,
            \array_keys($statuses),
        ));
    }

    private function parseUpdateStatus(mixed $status, string $key, int $index): string
    {
        if (!\is_string($status) || '' === \trim($status)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain update request section "%s" status at index %d must be a non-empty string.',
                    $key,
                    $index,
                ),
            );
        }

        return $status;
    }
}
