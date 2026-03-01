<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Domain\Dto\DomainExtension;
use RNIDS\Domain\Dto\DomainRegisterContact;
use RNIDS\Domain\Dto\DomainRegisterNameserver;
use RNIDS\Domain\Dto\DomainRegisterRequest;

/**
 * Validates and normalizes domain register payloads into typed DTO objects.
 */
final class DomainRegisterRequestFactory
{
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
     * } $request
     */
    public function fromArray(array $request): DomainRegisterRequest
    {
        return new DomainRegisterRequest(
            $this->requireString($request, 'name'),
            $this->optionalPositiveInt($request, 'period'),
            $this->optionalPeriodUnit($request),
            $this->optionalNameservers($request),
            $this->requireString($request, 'registrant'),
            $this->requireContacts($request),
            $this->optionalNullableString($request, 'authInfo'),
            $this->optionalRegisterExtension($request),
        );
    }

    /**
     * @param array<string, mixed> $request
     */
    private function requireString(array $request, string $key): string
    {
        $value = $request[$key] ?? null;

        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register request key "%s" must be a non-empty string.', $key),
            );
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
                \sprintf('Domain register request key "%s" must be a non-empty string when provided.', $key),
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
                \sprintf('Domain register request key "%s" must be a positive integer when provided.', $key),
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
                'Domain register request key "periodUnit" must be either "y" or "m" when provided.',
            );
        }

        return $unit;
    }

    /**
     * @param array{nameservers?: mixed} $request
     *
     * @return list<DomainRegisterNameserver>
     */
    private function optionalNameservers(array $request): array
    {
        $nameservers = $request['nameservers'] ?? [];

        if (!\is_array($nameservers)) {
            throw new \InvalidArgumentException('Domain register request key "nameservers" must be a list.');
        }

        return \array_values(\array_map(
            fn(mixed $nameserver, int $index): DomainRegisterNameserver => $this->parseNameserver(
                $nameserver,
                $index,
            ),
            $nameservers,
            \array_keys($nameservers),
        ));
    }

    private function parseNameserver(mixed $nameserver, int $index): DomainRegisterNameserver
    {
        if (!\is_array($nameserver)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register request nameserver at index %d must be an array.', $index),
            );
        }

        $name = $nameserver['name'] ?? null;
        if (!\is_string($name) || '' === \trim($name)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain register request nameserver at index %d must include non-empty "name".',
                    $index,
                ),
            );
        }

        return new DomainRegisterNameserver($name, $this->parseNameserverAddresses($nameserver, $index));
    }

    /**
     * @param array<string, mixed> $nameserver
     *
     * @return list<string>
     */
    private function parseNameserverAddresses(array $nameserver, int $index): array
    {
        $addresses = $nameserver['addresses'] ?? [];

        if (!\is_array($addresses)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain register request nameserver at index %d field "addresses" must be a list.',
                    $index,
                ),
            );
        }

        return \array_values(\array_map(
            fn(mixed $address, int $addressIndex): string =>
                $this->parseNameserverAddress($address, $index, $addressIndex),
            $addresses,
            \array_keys($addresses),
        ));
    }

    private function parseNameserverAddress(mixed $address, int $index, int $addressIndex): string
    {
        if (!\is_string($address) || '' === \trim($address)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain register request nameserver at index %d has invalid address at index %d.',
                    $index,
                    $addressIndex,
                ),
            );
        }

        return $address;
    }

    /**
     * @param array{contacts?: mixed} $request
     *
     * @return list<DomainRegisterContact>
     */
    private function requireContacts(array $request): array
    {
        $contacts = $request['contacts'] ?? null;

        if (!\is_array($contacts) || [] === $contacts) {
            throw new \InvalidArgumentException(
                'Domain register request key "contacts" must be a non-empty list.',
            );
        }

        $result = \array_values(\array_map(
            fn(mixed $contact, int $index): DomainRegisterContact => $this->parseContact($contact, $index),
            $contacts,
            \array_keys($contacts),
        ));

        $this->assertRequiredContactTypes($result);

        return $result;
    }

    private function parseContact(mixed $contact, int $index): DomainRegisterContact
    {
        if (!\is_array($contact)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register request contact at index %d must be an array.', $index),
            );
        }

        $type = $contact['type'] ?? null;
        if (!\is_string($type) || !\in_array($type, [ 'admin', 'tech', 'billing' ], true)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain register request contact at index %d has invalid "type" (allowed: admin, tech, billing).',
                    $index,
                ),
            );
        }

        $handle = $contact['handle'] ?? null;
        if (!\is_string($handle) || '' === \trim($handle)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Domain register request contact at index %d must include non-empty "handle".',
                    $index,
                ),
            );
        }

        return new DomainRegisterContact($type, $handle);
    }

    /**
     * @param list<DomainRegisterContact> $contacts
     */
    private function assertRequiredContactTypes(array $contacts): void
    {
        $types = [];

        foreach ($contacts as $contact) {
            $types[$contact->type] = true;
        }

        if (isset($types['admin']) && isset($types['tech'])) {
            return;
        }

        throw new \InvalidArgumentException(
            'Domain register request contacts must include at least one "admin" and one "tech" contact.',
        );
    }

    /**
     * @param array{extension?: mixed} $request
     */
    private function optionalRegisterExtension(array $request): ?DomainExtension
    {
        $extension = $request['extension'] ?? null;

        if (null === $extension) {
            return null;
        }

        if (!\is_array($extension)) {
            throw new \InvalidArgumentException(
                'Domain register request key "extension" must be an array when provided.',
            );
        }

        return new DomainExtension(
            $this->normalizeExtensionRemark($extension),
            $this->optionalBool($extension, 'isWhoisPrivacy'),
            $this->normalizeExtensionOperationMode($extension),
            $this->optionalBool($extension, 'notifyAdmin'),
            $this->optionalBool($extension, 'dnsSec'),
        );
    }

    /**
     * @param array<string, mixed> $extension
     */
    private function normalizeExtensionRemark(array $extension): ?string
    {
        $remark = $extension['remark'] ?? null;

        if (null === $remark) {
            return null;
        }

        if (!\is_string($remark) || '' === \trim($remark)) {
            throw new \InvalidArgumentException(
                'Domain register request extension key "remark" must be a non-empty string when provided.',
            );
        }

        return $remark;
    }

    /**
     * @param array<string, mixed> $extension
     */
    private function normalizeExtensionOperationMode(array $extension): ?string
    {
        $operationMode = $extension['operationMode'] ?? null;

        if (null === $operationMode) {
            return null;
        }

        if (!\is_string($operationMode) || !\in_array($operationMode, [ 'normal', 'secure' ], true)) {
            throw new \InvalidArgumentException(
                'Domain register request extension key "operationMode" must be "normal" or "secure" when provided.',
            );
        }

        return $operationMode;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function optionalBool(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_bool($value)) {
            throw new \InvalidArgumentException(
                \sprintf('Domain register request extension key "%s" must be a boolean when provided.', $key),
            );
        }

        return $value;
    }
}
