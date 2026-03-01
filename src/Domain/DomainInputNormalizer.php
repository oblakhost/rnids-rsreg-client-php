<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Domain\Dto\DomainInfoRequest;

final class DomainInputNormalizer
{
    private DomainNameserverNormalizer $nameserverNormalizer;

    public function __construct(DomainNameserverNormalizer $nameserverNormalizer)
    {
        $this->nameserverNormalizer = $nameserverNormalizer;
    }

    /**
     * @param array{names?: mixed}|list<mixed>|non-empty-string $request
     *
     * @return list<string>
     */
    public function normalizeCheckNames(string|array $request): array
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

    public function optionalHosts(?string $hosts): string
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

    public function requireDomainName(string $name): string
    {
        if ('' === \trim($name)) {
            throw new \InvalidArgumentException('Domain name must be a non-empty string.');
        }

        return $name;
    }

    /**
     * @param array{name?: mixed} $request
     */
    public function requireName(array $request): string
    {
        $name = $request['name'] ?? null;

        if (!\is_string($name) || '' === \trim($name)) {
            throw new \InvalidArgumentException('Domain info request key "name" must be a non-empty string.');
        }

        return $name;
    }

    /**
     * @param array{currentExpirationDate?: mixed} $request
     */
    public function requireCurrentExpirationDate(array $request): string
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
     * @param array<string, mixed> $request
     */
    public function optionalNullableString(array $request, string $key): ?string
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
    public function optionalPositiveInt(array $request, string $key): ?int
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
    public function optionalPeriodUnit(array $request): string
    {
        $unit = $request['periodUnit'] ?? 'y';

        if (!\is_string($unit) || !\in_array($unit, [ 'y', 'm' ], true)) {
            throw new \InvalidArgumentException(
                'Domain request key "periodUnit" must be either "y" or "m" when provided.',
            );
        }

        return $unit;
    }

    /**
     * @param array{operation?: mixed} $request
     */
    public function requireTransferOperation(array $request): string
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
    public function normalizeRegisterRequest(
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
            'nameservers' => $this->nameserverNormalizer->normalizeSimplifiedNameservers($nameservers),
            'period' => $years,
            'periodUnit' => 'y',
            'registrant' => $this->requireSimplifiedRegisterValue($registrant, 'registrant'),
        ];
    }

    /**
     * @param array{
     *   name?: mixed,
     *   currentExpirationDate?: mixed,
     *   period?: mixed,
     *   periodUnit?: mixed
     * }|non-empty-string $request
     * @param callable(string): string $resolveCurrentExpirationDate
     *
     * @return array{name: string, currentExpirationDate: string, period?: int, periodUnit?: string}
     */
    public function normalizeRenewRequest(
        string|array $request,
        ?int $years,
        callable $resolveCurrentExpirationDate,
    ): array {
        if (\is_array($request)) {
            return $request;
        }

        if (null === $years || $years <= 0) {
            throw new \InvalidArgumentException(
                'Domain renew years must be a positive integer when using simplified renew API.',
            );
        }

        $name = $this->requireDomainName($request);
        $expirationDate = $resolveCurrentExpirationDate($name);

        return [
            'currentExpirationDate' => $this->normalizeExpirationDateForRenew($expirationDate),
            'name' => $name,
            'period' => $years,
            'periodUnit' => 'y',
        ];
    }

    public function normalizeExpirationDateForRenew(string $expirationDate): string
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

    private function requireSimplifiedRegisterValue(?string $value, string $key): string
    {
        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register key "%s" must be a non-empty string in simplified API.', $key),
            );
        }

        return $value;
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
