<?php

declare(strict_types=1);

namespace RNIDS\Host;

use RNIDS\Host\Dto\HostAddress;
use RNIDS\Host\Dto\HostCheckRequest;
use RNIDS\Host\Dto\HostCreateRequest;
use RNIDS\Host\Dto\HostUpdateRequest;
use RNIDS\Host\Dto\HostUpdateSection;

/**
 * Validates and normalizes host payloads into typed DTO objects.
 */
final class HostRequestFactory
{
    /**
     * @param array{names?: mixed} $request
     */
    public function checkFromArray(array $request): HostCheckRequest
    {
        return new HostCheckRequest($this->requireNames($request));
    }

    /**
     * @param array{name?: mixed, addresses?: mixed} $request
     */
    public function createFromArray(array $request): HostCreateRequest
    {
        return new HostCreateRequest(
            $this->requireString(
                $request,
                'name',
                'Host create request key "%s" must be a non-empty string.',
            ),
            $this->optionalAddresses($request, 'addresses', 'Host create request key "%s" must be a list.'),
        );
    }

    /**
     * @param array{name?: mixed, add?: mixed, remove?: mixed, newName?: mixed} $request
     */
    public function updateFromArray(array $request): HostUpdateRequest
    {
        $add = $this->optionalUpdateSection($request, 'add');
        $remove = $this->optionalUpdateSection($request, 'remove');
        $newName = $this->optionalNullableString($request, 'newName');

        if (null === $add && null === $remove && null === $newName) {
            throw new \InvalidArgumentException(
                'Host update request must include at least one of "add", "remove", or "newName".',
            );
        }

        return new HostUpdateRequest(
            $this->requireString(
                $request,
                'name',
                'Host update request key "%s" must be a non-empty string.',
            ),
            $add,
            $remove,
            $newName,
        );
    }

    /**
     * @param array<string, mixed> $request
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

    private function assertNamesList(mixed $names): void
    {
        if (\is_array($names) && [] !== $names) {
            return;
        }

        throw new \InvalidArgumentException(
            'Host check request key "names" must be a non-empty list of strings.',
        );
    }

    private function requireNameString(mixed $name): string
    {
        if (!\is_string($name) || '' === \trim($name)) {
            throw new \InvalidArgumentException(
                'Host check request key "names" must contain only non-empty strings.',
            );
        }

        return $name;
    }

    /**
     * @param array<string, mixed> $request
     */
    private function requireString(array $request, string $key, string $errorPattern): string
    {
        $value = $request[$key] ?? null;

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(\sprintf($errorPattern, $key));
        }

        return $value;
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
                \sprintf('Host request key "%s" must be a non-empty string when provided.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return list<HostAddress>
     */
    private function optionalAddresses(array $request, string $key, string $listErrorPattern): array
    {
        $addresses = $request[$key] ?? [];

        if (!\is_array($addresses)) {
            throw new \InvalidArgumentException(\sprintf($listErrorPattern, $key));
        }

        return \array_values(\array_map(
            fn(mixed $address, int $index): HostAddress => $this->parseAddress($address, $key, $index),
            $addresses,
            \array_keys($addresses),
        ));
    }

    private function parseAddress(mixed $address, string $section, int $index): HostAddress
    {
        if (!\is_array($address)) {
            throw new \InvalidArgumentException(
                \sprintf('Host %s address at index %d must be an array.', $section, $index),
            );
        }

        $value = $address['address'] ?? null;
        $ipVersion = $address['ipVersion'] ?? 'v4';

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Host %s address at index %d must include non-empty "address".', $section, $index),
            );
        }

        if (!\is_string($ipVersion) || !\in_array($ipVersion, [ 'v4', 'v6' ], true)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Host %s address at index %d has invalid "ipVersion" (allowed: v4, v6).',
                    $section,
                    $index,
                ),
            );
        }

        return new HostAddress($value, $ipVersion);
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalUpdateSection(array $request, string $key): ?HostUpdateSection
    {
        $section = $request[$key] ?? null;

        if (null === $section) {
            return null;
        }

        if (!\is_array($section)) {
            throw new \InvalidArgumentException(
                \sprintf('Host update request key "%s" must be an array.', $key),
            );
        }

        $addresses = $this->optionalAddresses($section, 'addresses', 'Host update key "%s" must be a list.');
        $statuses = $this->optionalStatuses($section, 'statuses');

        if ([] === $addresses && [] === $statuses) {
            throw new \InvalidArgumentException(
                \sprintf('Host update request key "%s" must contain at least one address or status.', $key),
            );
        }

        return new HostUpdateSection($addresses, $statuses);
    }

    /**
     * @param array<string, mixed> $section
     *
     * @return list<string>
     */
    private function optionalStatuses(array $section, string $key): array
    {
        $statuses = $section[$key] ?? [];

        if (!\is_array($statuses)) {
            throw new \InvalidArgumentException(\sprintf('Host update key "%s" must be a list.', $key));
        }

        return \array_values(\array_map(
            static function (mixed $status, int $index): string {
                if (!\is_string($status) || '' === \trim($status)) {
                    throw new \InvalidArgumentException(
                        \sprintf('Host update status at index %d must be a non-empty string.', $index),
                    );
                }

                return $status;
            },
            $statuses,
            \array_keys($statuses),
        ));
    }
}
