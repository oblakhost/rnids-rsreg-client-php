<?php

declare(strict_types=1);

namespace RNIDS\Contact;

use RNIDS\Contact\Dto\ContactCheckItem;
use RNIDS\Contact\Dto\ContactCheckResponse;
use RNIDS\Contact\Dto\ContactCreateResponse;
use RNIDS\Contact\Dto\ContactInfoResponse;

final class ContactResponseMapper
{
    /**
     * @return list<array{id: string, available: bool, reason: string|null}>
     */
    public function mapCheckResponse(ContactCheckResponse $response): array
    {
        return \array_map(
            static fn(ContactCheckItem $item): array => [
                'available' => $item->available,
                'id' => $item->id,
                'reason' => $item->reason,
            ],
            $response->items,
        );
    }

    /**
     * @return array{id: string|null, createDate: \DateTimeImmutable|null}
     */
    public function mapCreateResponse(ContactCreateResponse $response): array
    {
        return [
            'createDate' => $response->createDate,
            'id' => $response->id,
        ];
    }

    /**
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
     * }
     */
    public function mapInfoResponse(ContactInfoResponse $response): array
    {
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
            'fax' => $response->fax,
            'id' => $response->id,
            'ident' => $response->ident,
            'identDescription' => $response->identDescription,
            'identExpiry' => $response->identExpiry,
            'identKind' => $response->identKind,
            'legalEntity' => $response->legalEntity,
            'postalInfo' => $postalInfo,
            'roid' => $response->roid,
            'statuses' => $response->statuses,
            'transferDate' => $response->transferDate,
            'updateClientId' => $response->updateClientId,
            'updateDate' => $response->updateDate,
            'vatNo' => $response->vatNo,
            'voice' => $response->voice,
        ];
    }

    /**
     * @return array{}
     */
    public function mapEmptyResponse(): array
    {
        return [];
    }
}
