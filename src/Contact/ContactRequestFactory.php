<?php

declare(strict_types=1);

namespace RNIDS\Contact;

use RNIDS\Contact\Dto\ContactAddress;
use RNIDS\Contact\Dto\ContactCheckRequest;
use RNIDS\Contact\Dto\ContactCreateRequest;
use RNIDS\Contact\Dto\ContactExtension;
use RNIDS\Contact\Dto\ContactPostalInfo;
use RNIDS\Contact\Dto\ContactUpdateRequest;

/**
 * Validates and normalizes contact payloads into typed DTO objects.
 */
final class ContactRequestFactory
{
    /** @deprecated Identification descriptions are supplied by the caller. */
    public const ENFORCED_IDENT_DESCRIPTION = 'Object Creation provided by Oblak Solutions.';

    private ContactIdPolicy $contactIdPolicy;

    public function __construct(?ContactIdPolicy $contactIdPolicy = null)
    {
        $this->contactIdPolicy = $contactIdPolicy ?? new ContactIdPolicy();
    }

    /**
     * @param array{ids: non-empty-list<non-empty-string>} $request
     */
    public function checkFromArray(array $request): ContactCheckRequest
    {
        return new ContactCheckRequest($this->requireIds($request));
    }

    /**
     * @param array{
     *   id?: string|null,
     *   postalInfo: array{
     *     type?: 'loc'|'int',
     *     name: string,
     *     organization?: non-empty-string|null,
     *     address: array{
     *       streets: non-empty-list<non-empty-string>,
     *       city: non-empty-string,
     *       countryCode: non-empty-string,
     *       province?: non-empty-string|null,
     *       postalCode?: non-empty-string|null
     *     }
     *   },
     *   voice: non-empty-string,
     *   fax?: non-empty-string|null,
     *   email: non-empty-string,
     *   authInfo?: non-empty-string|null,
     *   disclose?: 0|1|null,
     *   extension?: array{
     *     ident?: non-empty-string|null,
     *     identDescription?: non-empty-string|null,
     *     identExpiry?: non-empty-string|null,
     *     identKind?: non-empty-string|null,
     *     isLegalEntity?: non-empty-string|null,
     *     vatNo?: non-empty-string|null
     *   }|null
     * } $request
     */
    public function createFromArray(array $request): ContactCreateRequest
    {
        $extension = $this->optionalExtension($request);

        return new ContactCreateRequest(
            $this->contactIdPolicy->normalizeForCreate($request['id'] ?? null),
            $this->requirePostalInfoForCreate($request, $extension),
            $this->requireString(
                $request,
                'voice',
                'Contact create request key "%s" must be a non-empty string.',
            ),
            $this->optionalNullableString($request, 'fax'),
            $this->requireString(
                $request,
                'email',
                'Contact create request key "%s" must be a non-empty string.',
            ),
            $this->optionalNullableString($request, 'authInfo'),
            $this->optionalDisclose($request),
            $extension,
        );
    }

    /**
     * @param array{
     *   id: non-empty-string,
     *   addStatuses?: list<non-empty-string>|null,
     *   removeStatuses?: list<non-empty-string>|null,
     *   postalInfo?: array{
     *     type?: 'loc'|'int',
     *     name: string,
     *     organization?: non-empty-string|null,
     *     address: array{
     *       streets: non-empty-list<non-empty-string>,
     *       city: non-empty-string,
     *       countryCode: non-empty-string,
     *       province?: non-empty-string|null,
     *       postalCode?: non-empty-string|null
     *     }
     *   }|null,
     *   voice?: non-empty-string|null,
     *   fax?: string|null,
     *   email?: non-empty-string|null,
     *   authInfo?: string|null,
     *   disclose?: 0|1|null,
     *   extension?: array{
     *     ident?: string|null,
     *     identDescription?: string|null,
     *     identExpiry?: non-empty-string|null,
     *     identKind?: non-empty-string|null,
     *     isLegalEntity?: non-empty-string|null,
     *     vatNo?: string|null
     *   }|null
     * } $request
     */
    public function updateFromArray(array $request): ContactUpdateRequest
    {
        $extension = $this->optionalExtension($request, true);

        $dto = new ContactUpdateRequest(
            $this->contactIdPolicy->normalizeForUpdate($this->requireString(
                $request,
                'id',
                'Contact update request key "%s" must be a non-empty string.',
            )),
            $this->optionalStatuses($request, 'addStatuses'),
            $this->optionalStatuses($request, 'removeStatuses'),
            $this->optionalPostalInfo($request, $extension),
            $this->optionalNullableString($request, 'voice'),
            $this->optionalNullableString($request, 'fax', true),
            $this->optionalNullableString($request, 'email'),
            $this->optionalNullableString($request, 'authInfo', true),
            $this->optionalDisclose($request),
            $extension,
        );

        if (
            [] === $dto->addStatuses
            && [] === $dto->removeStatuses
            && null === $dto->postalInfo
            && null === $dto->voice
            && null === $dto->fax
            && null === $dto->email
            && null === $dto->authInfo
            && null === $dto->disclose
            && null === $dto->extension
        ) {
            throw new \InvalidArgumentException(
                'Contact update request must include at least one change field.',
            );
        }

        return $dto;
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return list<string>
     */
    private function requireIds(array $request): array
    {
        $ids = $request['ids'] ?? null;

        if (!\is_array($ids) || [] === $ids) {
            throw new \InvalidArgumentException(
                'Contact check request key "ids" must be a non-empty list of strings.',
            );
        }

        return \array_values(\array_map(
            fn(mixed $value, int $index): string =>
                $this->normalizeString(
                    $value,
                    'Contact check id at index %d must be a non-empty string.',
                    $index,
                ),
            $ids,
            \array_keys($ids),
        ));
    }

    /**
     * @param array<string, mixed> $request
     */
    private function requirePostalInfo(array $request): ContactPostalInfo
    {
        $postalInfo = $request['postalInfo'] ?? null;

        if (!\is_array($postalInfo)) {
            throw new \InvalidArgumentException('Contact request key "postalInfo" must be an array.');
        }

        return $this->parsePostalInfo($postalInfo);
    }

    /**
     * @param array<string, mixed> $request
     */
    private function requirePostalInfoForCreate(array $request, ?ContactExtension $extension): ContactPostalInfo
    {
        $postalInfo = $this->requirePostalInfo($request);

        if ('' !== \trim($postalInfo->name)) {
            return $postalInfo;
        }

        if ($this->allowsEmptyName($extension, $postalInfo)) {
            return $postalInfo;
        }

        throw new \InvalidArgumentException('Contact postalInfo key "name" must be a non-empty string.');
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalPostalInfo(array $request, ?ContactExtension $extension): ?ContactPostalInfo
    {
        $postalInfo = $request['postalInfo'] ?? null;

        if (null === $postalInfo) {
            return null;
        }

        if (!\is_array($postalInfo)) {
            throw new \InvalidArgumentException(
                'Contact request key "postalInfo" must be an array when provided.',
            );
        }

        $parsed = $this->parsePostalInfo($postalInfo);

        if ('' === \trim($parsed->name) && !$this->allowsEmptyName($extension, $parsed)) {
            throw new \InvalidArgumentException('Contact postalInfo key "name" must be a non-empty string.');
        }

        return $parsed;
    }

    /**
     * @param array<string, mixed> $postalInfo
     */
    private function parsePostalInfo(array $postalInfo, bool $allowEmpty = false): ContactPostalInfo
    {
        $type = $postalInfo['type'] ?? ContactPostalInfo::TYPE_LOC;

        if (!\is_string($type)) {
            throw new \InvalidArgumentException(
                'Contact postalInfo key "type" must be either "loc" or "int".',
            );
        }

        if (ContactPostalInfo::TYPE_LOC !== $type && ContactPostalInfo::TYPE_INT !== $type) {
            throw new \InvalidArgumentException(
                'Contact postalInfo key "type" must be either "loc" or "int".',
            );
        }

        $address = $postalInfo['address'] ?? null;

        if (!\is_array($address)) {
            throw new \InvalidArgumentException('Contact postalInfo key "address" must be an array.');
        }

        return new ContactPostalInfo(
            $type,
            $this->optionalStringAllowingEmpty($postalInfo, 'name'),
            $this->optionalNullableString($postalInfo, 'organization', $allowEmpty),
            $this->parseAddress($address, $allowEmpty),
        );
    }

    private function allowsEmptyName(
        ?ContactExtension $extension,
        ContactPostalInfo $postalInfo,
    ): bool {
        return null !== $extension
            && '1' === $extension->isLegalEntity
            && null !== $postalInfo->organization
            && '' !== \trim($postalInfo->organization);
    }

    /**
     * @param array<string, mixed> $address
     */
    private function parseAddress(array $address, bool $allowEmpty): ContactAddress
    {
        $streets = $address['streets'] ?? null;

        if (!\is_array($streets) || [] === $streets) {
            throw new \InvalidArgumentException(
                'Contact address key "streets" must be a non-empty list of strings.',
            );
        }

        $normalizedStreets = \array_values(\array_map(
            fn(mixed $street, int $index): string =>
                $this->normalizeString(
                    $street,
                    'Contact address street at index %d must be a non-empty string.',
                    $index,
                ),
            $streets,
            \array_keys($streets),
        ));

        return new ContactAddress(
            $normalizedStreets,
            $this->requireString($address, 'city', 'Contact address key "%s" must be a non-empty string.'),
            $this->requireString(
                $address,
                'countryCode',
                'Contact address key "%s" must be a non-empty string.',
            ),
            $this->optionalNullableString($address, 'province', $allowEmpty),
            $this->optionalNullableString($address, 'postalCode', $allowEmpty),
        );
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return list<string>
     */
    private function optionalStatuses(array $request, string $key): array
    {
        $statuses = $request[$key] ?? [];

        if (!\is_array($statuses)) {
            throw new \InvalidArgumentException(
                \sprintf('Contact request key "%s" must be a list.', $key),
            );
        }

        return \array_values(\array_map(
            fn(mixed $status, int $index): string =>
                $this->normalizeString(
                    $status,
                    'Contact status at index %d must be a non-empty string.',
                    $index,
                ),
            $statuses,
            \array_keys($statuses),
        ));
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalDisclose(array $request): ?int
    {
        $disclose = $request['disclose'] ?? null;

        if (null === $disclose) {
            return null;
        }

        if (!\is_int($disclose) || !\in_array($disclose, [ 0, 1 ], true)) {
            throw new \InvalidArgumentException(
                'Contact request key "disclose" must be 0 or 1 when provided.',
            );
        }

        return $disclose;
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalExtension(array $request, bool $allowEmpty = false): ?ContactExtension
    {
        $extension = $request['extension'] ?? null;

        if (null === $extension) {
            return null;
        }

        if (!\is_array($extension)) {
            throw new \InvalidArgumentException(
                'Contact request key "extension" must be an array when provided.',
            );
        }

        $parsed = new ContactExtension(
            $this->optionalNullableString($extension, 'ident', $allowEmpty),
            $this->optionalNullableString($extension, 'identDescription', $allowEmpty),
            $this->optionalNullableString($extension, 'identExpiry'),
            $this->optionalNullableString($extension, 'identKind'),
            $this->optionalNullableString($extension, 'isLegalEntity'),
            $this->optionalNullableString($extension, 'vatNo', $allowEmpty),
        );

        if (
            null === $parsed->ident
            && null === $parsed->identDescription
            && null === $parsed->identExpiry
            && null === $parsed->identKind
            && null === $parsed->isLegalEntity
            && null === $parsed->vatNo
        ) {
            return null;
        }

        return $parsed;
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
    private function optionalNullableString(array $request, string $key, bool $allowEmpty = false): ?string
    {
        $value = $request[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_string($value) || ('' === \trim($value) && (!$allowEmpty || '' !== $value))) {
            throw new \InvalidArgumentException(
                \sprintf('Contact request key "%s" must be a non-empty string when provided.', $key),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $request
     */
    private function optionalStringAllowingEmpty(array $request, string $key): string
    {
        if (!\array_key_exists($key, $request) || !\is_string($request[$key])) {
            throw new \InvalidArgumentException(
                \sprintf('Contact postalInfo key "%s" must be a string.', $key),
            );
        }

        return $request[$key];
    }

    private function normalizeString(mixed $value, string $errorPattern, int $index): string
    {
        if (!\is_string($value) || '' === \trim($value)) {
            throw new \InvalidArgumentException(\sprintf($errorPattern, $index));
        }

        return $value;
    }
}
