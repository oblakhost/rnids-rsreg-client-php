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
use RNIDS\Xml\Response\LastResponseMetadata;

/**
 * Provides domain command operations for check, info, and register flows.
 */
final class DomainService
{
    private CommandExecutor $executor;

    private ClTridGenerator $tridGenerator;

    private DomainRegisterRequestFactory $registerRequestFactory;

    private static function normalizeNameserverAddress(mixed $address, int $addressIndex): string
    {
        if (!\is_string($address) || '' === \trim($address)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register nameserver address at index %d is invalid.', $addressIndex),
            );
        }

        return $address;
    }

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
        ?LastResponseMetadata $lastResponseMetadata = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport, null, $lastResponseMetadata);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('DOMAIN');
        $this->registerRequestFactory = $registerRequestFactory ?? new DomainRegisterRequestFactory();
    }

    /**
     * @param array{names?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return array{items: list<array{name: string, available: bool, reason: string|null}>}
     */
    public function check(string|array $request): array
    {
        $xml = (new DomainCheckRequestBuilder())->build(
            new DomainCheckRequest($this->normalizeCheckNames($request)),
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
        ];
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
        $xml = (new DomainInfoRequestBuilder())->build(
            new DomainInfoRequest(
                $this->requireDomainName($name),
                $this->optionalHosts($hosts),
            ),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainInfoResponseParser())->parse($responseXml, $metadata),
        );

        return [
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
     * }|non-empty-string $request
     * @param non-empty-string|null $registrant
     * @param non-empty-string|null $adminContact
     * @param non-empty-string|null $techContact
     * @param non-empty-string|array<int, mixed>|null $nameservers
     * @param int|null $years
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
        $normalizedRequest = $this->normalizeRegisterRequest(
            $request,
            $registrant,
            $adminContact,
            $techContact,
            $nameservers,
            $years,
            $authInfo,
            $extension,
        );

        $xml = (new DomainRegisterRequestBuilder())->build(
            $this->registerRequestFactory->fromArray($normalizedRequest),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainRegisterResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'createDate' => $response->createDate,
            'expirationDate' => $response->expirationDate,
            'name' => $response->name,
        ];
    }

    /**
     * @param array{
     *   name?: mixed,
     *   currentExpirationDate?: mixed,
     *   period?: mixed,
     *   periodUnit?: mixed
     * }|non-empty-string $request
     *
     * @return array{name: string|null, expirationDate: string|null}
     */
    public function renew(string|array $request, ?int $years = null): array
    {
        $normalizedRequest = $this->normalizeRenewRequest($request, $years);

        $xml = (new DomainRenewRequestBuilder())->build(
            new DomainRenewRequest(
                $this->requireName($normalizedRequest),
                $this->requireCurrentExpirationDate($normalizedRequest),
                $this->optionalPositiveInt($normalizedRequest, 'period'),
                $this->optionalPeriodUnit($normalizedRequest),
            ),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainRenewResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'expirationDate' => $response->expirationDate,
            'name' => $response->name,
        ];
    }

    /**
     * @return array{}
     */
    public function delete(string $name): array
    {
        $xml = (new DomainDeleteRequestBuilder())->build(
            new DomainDeleteRequest($this->requireDomainName($name)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainDeleteResponseParser())->parse($responseXml, $metadata),
        );

        return [];
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
            'actionClientId' => $response->actionClientId,
            'actionDate' => $response->actionDate,
            'expirationDate' => $response->expirationDate,
            'name' => $response->name,
            'requestClientId' => $response->requestClientId,
            'requestDate' => $response->requestDate,
            'transferStatus' => $response->transferStatus,
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
     * }|non-empty-string $request
     * @param non-empty-string|null $registrant
     * @param non-empty-string|null $adminContact
     * @param non-empty-string|null $techContact
     * @param non-empty-string|array<int, mixed>|null $nameservers
     * @param int|null $years
     *
     * @return array{
     *   name: string,
     *   period: int,
     *   periodUnit: string,
     *   nameservers: list<array{name: string, addresses?: list<string>}>,
     *   registrant: string,
     *   contacts: list<array{type: string, handle: string}>,
     *   authInfo?: string,
     *   extension?: array<string, mixed>
     * }|array<string, mixed>
     */
    private function normalizeRegisterRequest(
        string|array $request,
        ?string $registrant,
        ?string $adminContact,
        ?string $techContact,
        string|array|null $nameservers,
        ?int $years,
        ?string $authInfo,
        ?array $extension,
    ): array {
        if (\is_array($request)) {
            return $request;
        }

        if (null === $years || $years <= 0) {
            throw new \InvalidArgumentException(
                'Domain register years must be a positive integer in simplified register API.',
            );
        }

        return [
            'authInfo' => $authInfo,
            'contacts' => [
                [
                    'handle' => $this->requireSimplifiedRegisterValue($adminContact, 'adminContact'),
                    'type' => 'admin',
                ],
                [
                    'handle' => $this->requireSimplifiedRegisterValue($techContact, 'techContact'),
                    'type' => 'tech',
                ],
            ],
            'extension' => $extension,
            'name' => $this->requireDomainName($request),
            'nameservers' => $this->normalizeSimplifiedNameservers($nameservers),
            'period' => $years,
            'periodUnit' => 'y',
            'registrant' => $this->requireSimplifiedRegisterValue($registrant, 'registrant'),
        ];
    }

    private function requireSimplifiedRegisterValue(?string $value, string $key): string
    {
        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register key "%s" must be a non-empty string in simplified API.', $key),
            );
        }

        return $value;
    }

    /**
     * @param non-empty-string|array<int, mixed>|null $nameservers
     *
     * @return list<array{name: string, addresses?: list<string>}>
     */
    private function normalizeSimplifiedNameservers(string|array|null $nameservers): array
    {
        if (null === $nameservers) {
            throw new \InvalidArgumentException(
                'Domain register simplified API requires at least one nameserver.',
            );
        }

        if (\is_string($nameservers)) {
            return [ [ 'name' => $this->requireDomainName($nameservers) ] ];
        }

        if ([] === $nameservers) {
            throw new \InvalidArgumentException(
                'Domain register simplified API requires at least one nameserver.',
            );
        }

        return \array_values(\array_map(
            fn(mixed $nameserver, int $index): array => $this->normalizeSingleNameserver($nameserver, $index),
            $nameservers,
            \array_keys($nameservers),
        ));
    }

    /**
     * @return array{name: string, addresses?: list<string>}
     */
    private function normalizeSingleNameserver(mixed $nameserver, int $index): array
    {
        if (\is_string($nameserver)) {
            return [ 'name' => $this->requireDomainName($nameserver) ];
        }

        if (!\is_array($nameserver)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register nameserver at index %d is invalid.', $index),
            );
        }

        $name = $this->extractNameserverName($nameserver, $index);
        $addresses = $this->extractNameserverAddresses($nameserver, $index);

        if ([] === $addresses) {
            return [ 'name' => $name ];
        }

        return [
            'addresses' => $addresses,
            'name' => $name,
        ];
    }

    /**
     * @param array<string, mixed> $nameserver
     */
    private function extractNameserverName(array $nameserver, int $index): string
    {
        $name = $nameserver['name'] ?? null;

        if (!\is_string($name) || '' === \trim($name)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register nameserver at index %d requires non-empty "name".', $index),
            );
        }

        return $name;
    }

    /**
     * @param array<string, mixed> $nameserver
     *
     * @return list<string>
     */
    private function extractNameserverAddresses(array $nameserver, int $index): array
    {
        $addresses = $nameserver['addresses'] ?? null;

        if (null === $addresses) {
            return [];
        }

        if (!\is_array($addresses)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register nameserver at index %d field "addresses" must be a list.', $index),
            );
        }

        return \array_values(\array_map(
            static fn(mixed $address, int $addressIndex): string =>
                self::normalizeNameserverAddress($address, $addressIndex),
            $addresses,
            \array_keys($addresses),
        ));
    }

    /**
     * @param array{names?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return list<string>
     */
    private function normalizeCheckNames(string|array $request): array
    {
        if (\is_string($request)) {
            return [ $this->requireNameString($request) ];
        }

        if (isset($request['names'])) {
            return $this->requireNames($request);
        }

        $this->assertNamesList($request);

        $result = [];

        foreach ($request as $name) {
            $result[] = $this->requireNameString($name);
        }

        return $result;
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

    private function optionalHosts(?string $hosts): string
    {
        if (null === $hosts) {
            return DomainInfoRequest::HOSTS_ALL;
        }

        $allowedHosts = [
            DomainInfoRequest::HOSTS_ALL,
            DomainInfoRequest::HOSTS_DELEGATED,
            DomainInfoRequest::HOSTS_SUBORDINATE,
            DomainInfoRequest::HOSTS_NONE,
        ];

        if (!\in_array($hosts, $allowedHosts, true)) {
            throw new \InvalidArgumentException(
                'Domain info request key "hosts" must be one of "all", "del", "sub", or "none".',
            );
        }

        return $hosts;
    }

    private function requireDomainName(string $name): string
    {
        if ('' === \trim($name)) {
            throw new \InvalidArgumentException('Domain name must be a non-empty string.');
        }

        return $name;
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
     * @param array{
     *   name?: mixed,
     *   currentExpirationDate?: mixed,
     *   period?: mixed,
     *   periodUnit?: mixed
     * }|non-empty-string $request
     *
     * @return array{name: string, currentExpirationDate: string, period?: int, periodUnit?: string}
     */
    private function normalizeRenewRequest(string|array $request, ?int $years): array
    {
        if (\is_array($request)) {
            return $request;
        }

        if (null === $years || $years <= 0) {
            throw new \InvalidArgumentException(
                'Domain renew years must be a positive integer when using simplified renew API.',
            );
        }

        $name = $this->requireDomainName($request);
        $expirationDate = $this->resolveCurrentExpirationDate($name);

        return [
            'currentExpirationDate' => $expirationDate,
            'name' => $name,
            'period' => $years,
            'periodUnit' => 'y',
        ];
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

        return $this->normalizeExpirationDateForRenew($expirationDate);
    }

    private function normalizeExpirationDateForRenew(string $expirationDate): string
    {
        if (1 === \preg_match('/^\d{4}-\d{2}-\d{2}$/', $expirationDate)) {
            return $expirationDate;
        }

        $dateOnly = \substr($expirationDate, 0, 10);

        if (1 === \preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOnly)) {
            return $dateOnly;
        }

        throw new \InvalidArgumentException(
            'Resolved domain expiration date has invalid format for renew request.',
        );
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
