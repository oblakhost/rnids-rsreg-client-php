<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Connection\Transport;
use RNIDS\Domain\Dto\DomainCheckRequest;
use RNIDS\Domain\Dto\DomainDeleteRequest;
use RNIDS\Domain\Dto\DomainExtension;
use RNIDS\Domain\Dto\DomainInfoRequest;
use RNIDS\Domain\Dto\DomainNameserverAddress;
use RNIDS\Domain\Dto\DomainRegisterContact;
use RNIDS\Domain\Dto\DomainRegisterNameserver;
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
 *
 * @phpstan-type CheckNamesInput non-empty-array<array-key, non-empty-string>
 * @phpstan-type NameserverAddressInput non-empty-string|array{
 *   address: non-empty-string, ipVersion: 'v4'|'v6'
 * }
 * @phpstan-type NameserverInput array{
 *   name: non-empty-string, addresses?: array<int, NameserverAddressInput>|null
 * }
 * @phpstan-type ContactInput array{type: 'admin'|'tech'|'billing', handle: non-empty-string}
 * @phpstan-type DsRecordInput array{
 *   keyTag: int<0, 65535>, alg: 3|5|6|7|8|10|13|14, digestType: 1|2|3|4, digest: non-empty-string
 * }
 * @phpstan-type RegisterExtensionInput array{
 *   isWhoisPrivacy?: bool|null, operationMode?: 'normal'|'secure'|null,
 *   notifyAdmin?: bool|null, dnsSec?: bool|null, remark?: non-empty-string|null
 * }
 * @phpstan-type UpdateExtensionInput array{
 *   isWhoisPrivacy?: bool|null, operationMode?: 'normal'|'secure'|null,
 *   notifyAdmin?: bool|null, dnsSec?: bool|null, remark?: string|null
 * }
 * @phpstan-type UpdateSectionInput array{
 *   contacts?: array<int, ContactInput>|null, statuses?: array<int, non-empty-string>|null,
 *   nameservers?: non-empty-string|array<int, non-empty-string|NameserverInput>|null
 * }
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
     * @param array{names: CheckNamesInput}|CheckNamesInput|non-empty-string $request
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
     *   statuses: list<string>,
     *   registrant: string|null,
     *   adminContact: string|null,
     *   techContact: string|null,
     *   nameservers: array<string, array{ipv4: list<string>, ipv6: list<string>}>,
     *   clientId: string|null,
     *   createClientId: string|null,
     *   updateClientId: string|null,
     *   createDate: \DateTimeImmutable|null,
     *   updateDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null,
     *   whoisPrivacy: bool,
     *   isDomainVerified: bool,
     *   domainVerifiedOn: \DateTimeImmutable|null,
     *   domainVerificationRequestExpiresOn: \DateTimeImmutable|null,
     *   isWhoisPrivacyPaid: bool,
     *   operationMode: string|null,
     *   notifyAdmin: bool,
     *   dnsSec: bool,
     *   dnssec: array{records: list<array{keyTag: int, alg: int, digestType: int, digest: string}>},
     *   hosts: list<string>,
     *   whoisPrivacyPaidUntil: \DateTimeImmutable|null,
     *   remark: string|null
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
     * Both admin and tech contact types are required in the full request.
     *
     * @param array{
     *   name: non-empty-string,
     *   period?: positive-int|null,
     *   periodUnit?: 'y'|'m'|null,
     *   nameservers?: array<int, NameserverInput>|null,
     *   registrant: non-empty-string,
     *   contacts: non-empty-array<int, ContactInput>,
     *   authInfo?: non-empty-string|null,
     *   extension?: RegisterExtensionInput|null,
     *   dnssec?: array{records: non-empty-list<DsRecordInput>}|null
     * }|non-empty-string $request
     * @param non-empty-string|null $registrant
     * @param non-empty-string|null $adminContact
     * @param non-empty-string|null $techContact
     * @param non-empty-string|array<int, non-empty-string|NameserverInput>|null $nameservers
     * @param positive-int|null $years
     * @param non-empty-string|null $authInfo
     * @param RegisterExtensionInput|null $extension
     *
     * @return array{name: string|null, createDate: \DateTimeImmutable|null, expirationDate: \DateTimeImmutable|null}
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
     * Renews a domain for the requested number of years.
     *
     * If the current expiry date is not provided, it is resolved from {@see self::info()}.
     *
     * @param non-empty-string $domain
     * @param int $years
     * @param null|string|\DateTimeInterface $expiry Current expiration date used for EPP renew matching.
     *
     * @return array{domain: string, expiryDate: \DateTimeImmutable|null}
     */
    public function renew(string $domain, int $years = 1, null|string|\DateTimeInterface $expiry = null): array
    {
        $name = $this->inputNormalizer->requireDomainName($domain);

        if ($years < 1 || $years > 10) {
            throw new \InvalidArgumentException('Domain renew years must be between 1 and 10.');
        }

        $resolvedExpiry = $this->resolveRenewExpiryDate($name, $expiry);

        $xml = $this->renewRequestBuilder->build(
            new DomainRenewRequest(
                $name,
                $resolvedExpiry,
                $years,
                'y',
            ),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->renewResponseParser->parse($responseXml, $metadata),
        );

        $renewData = $this->responseMapper->mapRenewResponse($response);

        return [
            'domain' => $name,
            'expiryDate' => $renewData['expirationDate'],
        ];
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
     *   name: non-empty-string,
     *   add?: UpdateSectionInput|null,
     *   remove?: UpdateSectionInput|null,
     *   registrant?: non-empty-string|null,
     *   authInfo?: non-empty-string|null,
     *   extension?: UpdateExtensionInput|null,
     *   dnssec?: array{
     *     add?: list<DsRecordInput>, remove?: list<DsRecordInput>, removeAll?: bool
     *   }|null
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
     * Approves a domain transfer using the provided transfer code.
     *
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
     * }
     */
    public function transfer(string $domain, string $transferCode): array
    {
        $name = $this->inputNormalizer->requireDomainName($domain);

        if ('' === \trim($transferCode)) {
            throw new \InvalidArgumentException('Domain transfer code must be a non-empty string.');
        }

        return $this->executeTransferOperation(
            DomainTransferRequest::OPERATION_APPROVE,
            $name,
            $transferCode,
        );
    }

    /**
     * Starts the transfer flow and requests transfer code-related state from the registry.
     *
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
     * }
     */
    public function getCode(string $domain): array
    {
        return $this->executeTransferOperation(
            DomainTransferRequest::OPERATION_REQUEST,
            $this->inputNormalizer->requireDomainName($domain),
        );
    }

    /**
     * Queries current transfer state for a domain.
     *
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
     * }
     */
    public function getState(string $domain): array
    {
        return $this->executeTransferOperation(
            DomainTransferRequest::OPERATION_QUERY,
            $this->inputNormalizer->requireDomainName($domain),
        );
    }

    /**
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
     * }
     */
    public function transferRequest(string $domain, ?string $authInfo = null): array
    {
        if (null !== $authInfo && '' === \trim($authInfo)) {
            throw new \InvalidArgumentException('Domain transfer authInfo must be non-empty when provided.');
        }

        return $this->executeTransferOperation(
            DomainTransferRequest::OPERATION_REQUEST,
            $this->inputNormalizer->requireDomainName($domain),
            $authInfo,
        );
    }

    /**
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
     * }
     */
    public function transferQuery(string $domain, ?string $authInfo = null): array
    {
        if (null !== $authInfo && '' === \trim($authInfo)) {
            throw new \InvalidArgumentException('Domain transfer authInfo must be non-empty when provided.');
        }

        return $this->executeTransferOperation(
            DomainTransferRequest::OPERATION_QUERY,
            $this->inputNormalizer->requireDomainName($domain),
            $authInfo,
        );
    }

    /**
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
     * }
     */
    public function transferApprove(string $domain, ?string $authInfo = null): array
    {
        if (null !== $authInfo && '' === \trim($authInfo)) {
            throw new \InvalidArgumentException('Domain transfer authInfo must be non-empty when provided.');
        }

        return $this->executeTransferOperation(
            DomainTransferRequest::OPERATION_APPROVE,
            $this->inputNormalizer->requireDomainName($domain),
            $authInfo,
        );
    }

    /**
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
     * }
     */
    public function transferCancel(string $domain, ?string $authInfo = null): array
    {
        if (null !== $authInfo && '' === \trim($authInfo)) {
            throw new \InvalidArgumentException('Domain transfer authInfo must be non-empty when provided.');
        }

        return $this->executeTransferOperation(
            DomainTransferRequest::OPERATION_CANCEL,
            $this->inputNormalizer->requireDomainName($domain),
            $authInfo,
        );
    }

    /**
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
     * }
     */
    public function transferReject(string $domain, ?string $authInfo = null): array
    {
        if (null !== $authInfo && '' === \trim($authInfo)) {
            throw new \InvalidArgumentException('Domain transfer authInfo must be non-empty when provided.');
        }

        return $this->executeTransferOperation(
            DomainTransferRequest::OPERATION_REJECT,
            $this->inputNormalizer->requireDomainName($domain),
            $authInfo,
        );
    }

    /**
     * @return array{
     *   name: string|null,
     *   transferStatus: string|null,
     *   requestClientId: string|null,
     *   requestDate: \DateTimeImmutable|null,
     *   actionClientId: string|null,
     *   actionDate: \DateTimeImmutable|null,
     *   expirationDate: \DateTimeImmutable|null
     * }
     */
    private function executeTransferOperation(string $operation, string $name, ?string $authInfo = null): array
    {
        $xml = $this->transferRequestBuilder->build(
            new DomainTransferRequest(
                $operation,
                $name,
                null,
                'y',
                $authInfo,
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

        if (!$expirationDate instanceof \DateTimeImmutable) {
            throw new \InvalidArgumentException(
                'Unable to resolve current expiration date for simplified domain renew API.',
            );
        }

        return $expirationDate->format('Y-m-d');
    }

    private function resolveRenewExpiryDate(string $name, null|string|\DateTimeInterface $expiry): string
    {
        if (null === $expiry) {
            return $this->resolveCurrentExpirationDate($name);
        }

        if ($expiry instanceof \DateTimeInterface) {
            return $expiry->format('Y-m-d');
        }

        return $this->inputNormalizer->normalizeExpirationDateForRenew($expiry);
    }

    /**
     * @param array{
     *   name?: mixed,
     *   add?: mixed,
     *   remove?: mixed,
     *   registrant?: mixed,
     *   authInfo?: mixed,
     *   extension?: mixed,
     *   dnssec?: array{
     *     add?: list<array{keyTag: int, alg: int, digestType: int, digest: string}>,
     *     remove?: list<array{keyTag: int, alg: int, digestType: int, digest: string}>,
     *     removeAll?: bool
     *   }|null
     * } $request
     */
    private function buildUpdateRequest(array $request): DomainUpdateRequest
    {
        $allowed = [ 'name', 'add', 'remove', 'registrant', 'authInfo', 'extension', 'dnssec' ];
        if ([] !== \array_diff(\array_keys($request), $allowed)) {
            throw new \InvalidArgumentException('Unknown domain update request key.');
        }

        $name = $this->inputNormalizer->requireName($request);
        $add = $this->parseUpdateSection($request['add'] ?? null, 'add');
        $remove = $this->parseUpdateSection($request['remove'] ?? null, 'remove');
        $registrant = $this->inputNormalizer->optionalNullableString($request, 'registrant');
        $authInfo = $this->inputNormalizer->optionalNullableString($request, 'authInfo');
        $extension = $this->parseUpdateExtension($request['extension'] ?? null);
        $dnssec = (new DomainDnssecFactory())->update($request['dnssec'] ?? null);

        $baseChanges = \array_filter(
            [ $add, $remove, $registrant, $authInfo, $extension ],
            static fn(mixed $change): bool => null !== $change,
        );
        $this->assertStandaloneRegistrantChange($registrant, \count($baseChanges));
        $hasBaseChanges = [] !== $baseChanges;
        if (null !== $dnssec && $hasBaseChanges) {
            throw new \InvalidArgumentException(
                'RNIDS DNSSEC updates must be sent separately from other domain changes.',
            );
        }

        if (!$hasBaseChanges && null === $dnssec) {
            throw new \InvalidArgumentException(
                'Domain update request must include at least one of "add", "remove", "registrant", or "authInfo".',
            );
        }

        return new DomainUpdateRequest($name, $add, $remove, $registrant, $authInfo, $extension, $dnssec);
    }

    private function assertStandaloneRegistrantChange(?string $registrant, int $changeCount): void
    {
        if (null !== $registrant && $changeCount > 1) {
            throw new \InvalidArgumentException(
                'RNIDS registrant updates must be sent separately from other domain changes.',
            );
        }
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

        if ([] !== \array_diff(\array_keys($section), [ 'contacts', 'statuses', 'nameservers' ])) {
            throw new \InvalidArgumentException('Unknown domain update section key.');
        }

        $nameservers = $this->parseUpdateNameservers($section['nameservers'] ?? []);
        $contacts = $this->parseUpdateContacts($section, $key);
        $statuses = $this->parseUpdateStatuses($section, $key);

        if ([] === $contacts && [] === $statuses && [] === $nameservers) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain update request section "%s" must include at least one of "contacts" or "statuses".',
                    $key,
                ),
            );
        }

        return new DomainUpdateSection($contacts, $statuses, $nameservers);
    }

    /** @return list<DomainRegisterNameserver> */
    private function parseUpdateNameservers(mixed $nameservers): array
    {
        if ([] === $nameservers) {
            return [];
        }

        if (!\is_array($nameservers) && !\is_string($nameservers)) {
            throw new \InvalidArgumentException('Domain update nameservers must be a hostname or list.');
        }

        $normalized = (new DomainNameserverNormalizer())->normalizeSimplifiedNameservers($nameservers);

        return \array_map(
            static fn(array $nameserver): DomainRegisterNameserver => new DomainRegisterNameserver(
                $nameserver['name'],
                \array_map(
                    static fn(array $address): DomainNameserverAddress => new DomainNameserverAddress(
                        $address['address'],
                        $address['ipVersion'],
                    ),
                    $nameserver['addresses'] ?? [],
                ),
            ),
            $normalized,
        );
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

    private function parseUpdateExtension(mixed $extension): ?DomainExtension
    {
        if (null === $extension) {
            return null;
        }

        if (!\is_array($extension)) {
            throw new \InvalidArgumentException(
                'Domain update request key "extension" must be an array when provided.',
            );
        }

        $allowed = [ 'remark', 'isWhoisPrivacy', 'operationMode', 'notifyAdmin', 'dnsSec' ];
        if ([] !== \array_diff(\array_keys($extension), $allowed)) {
            throw new \InvalidArgumentException('Unknown domain update extension key.');
        }

        if ([] === \array_filter($extension, static fn(mixed $value): bool => null !== $value)) {
            return null;
        }

        return new DomainExtension(
            $this->parseUpdateExtensionRemark($extension),
            $this->parseUpdateExtensionBool($extension, 'isWhoisPrivacy'),
            $this->parseUpdateExtensionOperationMode($extension),
            $this->parseUpdateExtensionBool($extension, 'notifyAdmin'),
            $this->parseUpdateExtensionBool($extension, 'dnsSec'),
        );
    }

    /**
     * @param array<string, mixed> $extension
     */
    private function parseUpdateExtensionRemark(array $extension): ?string
    {
        $remark = $extension['remark'] ?? null;

        if (null === $remark) {
            return null;
        }

        if (!\is_string($remark)) {
            throw new \InvalidArgumentException(
                'Domain update request extension key "remark" must be a string when provided.',
            );
        }

        return $remark;
    }

    /**
     * @param array<string, mixed> $extension
     */
    private function parseUpdateExtensionOperationMode(array $extension): ?string
    {
        $operationMode = $extension['operationMode'] ?? null;

        if (null === $operationMode) {
            return null;
        }

        if (!\is_string($operationMode) || !\in_array($operationMode, [ 'normal', 'secure' ], true)) {
            throw new \InvalidArgumentException(
                'Domain update request extension key "operationMode" must be "normal" or "secure" when provided.',
            );
        }

        return $operationMode;
    }

    /**
     * @param array<string, mixed> $extension
     */
    private function parseUpdateExtensionBool(array $extension, string $key): ?bool
    {
        $value = $extension[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_bool($value)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain update request extension key "%s" must be a boolean when provided.',
                    $key,
                ),
            );
        }

        return $value;
    }
}
