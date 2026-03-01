<?php

declare(strict_types=1);

namespace Tests\Support;

final class ContactFixtureFactory
{
    public static function forSeed(string $seed): self
    {
        if ('' === \trim($seed)) {
            throw new \InvalidArgumentException('Contact fixture seed must be a non-empty string.');
        }

        return new self($seed);
    }

    public function __construct(
        private readonly string $seed,
        private readonly string $runToken = '',
    ) {
    }

    public function withRunToken(string $runToken): self
    {
        if ('' === \trim($runToken)) {
            throw new \InvalidArgumentException('Contact fixture run token must be a non-empty string.');
        }

        return new self($this->seed, $runToken);
    }

    /**
     * @return non-empty-string
     */
    public function contactId(string $profile): string
    {
        $base = \strtoupper(\substr(\sha1($this->seed . '-' . $profile), 0, 8));
        $token = '' === $this->runToken ? '' : '-' . \strtoupper($this->runToken);

        return 'C-' . $base . $token;
    }

    /**
     * @return array{
     *   id: non-empty-string,
     *   postalInfo: array{
     *     type: 'loc',
     *     name: non-empty-string,
     *     organization: non-empty-string,
     *     address: array{
     *       streets: list<non-empty-string>,
     *       city: non-empty-string,
     *       province: non-empty-string,
     *       postalCode: non-empty-string,
     *       countryCode: 'RS'
     *     }
     *   },
     *   voice: non-empty-string,
     *   email: non-empty-string,
     *   authInfo: non-empty-string,
     *   disclose: 0,
     *   extension: array{
     *     ident: non-empty-string,
     *     identDescription: non-empty-string,
     *     identKind: 'natId',
     *     isLegalEntity: '0'
     *   }
     * }
     */
    public function individualCreatePayload(): array
    {
        $id = $this->contactId('individual');

        return [
            'authInfo' => 'Auth-' . $id,
            'disclose' => 0,
            'email' => \strtolower($id) . '@example.rs',
            'extension' => [
                'ident' => 'IND-' . \substr($id, 2),
                'identDescription' => 'Individual profile',
                'identKind' => 'natId',
                'isLegalEntity' => '0',
            ],
            'id' => $id,
            'postalInfo' => [
                'address' => [
                    'city' => 'Belgrade',
                    'countryCode' => 'RS',
                    'postalCode' => '11000',
                    'province' => 'BG',
                    'streets' => [ 'Knez Mihailova 1' ],
                ],
                'name' => 'Individual ' . \substr($id, 2, 6),
                'organization' => 'RNIDS Test Person',
                'type' => 'loc',
            ],
            'voice' => '+381.111111',
        ];
    }

    /**
     * @return array{
     *   id: non-empty-string,
     *   postalInfo: array{
     *     type: 'loc',
     *     name: non-empty-string,
     *     organization: non-empty-string,
     *     address: array{
     *       streets: list<non-empty-string>,
     *       city: non-empty-string,
     *       province: non-empty-string,
     *       postalCode: non-empty-string,
     *       countryCode: 'RS'
     *     }
     *   },
     *   voice: non-empty-string,
     *   email: non-empty-string,
     *   authInfo: non-empty-string,
     *   disclose: 0,
     *   extension: array{
     *     ident: non-empty-string,
     *     identDescription: non-empty-string,
     *     identKind: 'companyNumber',
     *     isLegalEntity: '1',
     *     vatNo: non-empty-string
     *   }
     * }
     */
    public function companyCreatePayload(): array
    {
        $id = $this->contactId('company');

        return [
            'authInfo' => 'Auth-' . $id,
            'disclose' => 0,
            'email' => \strtolower($id) . '@example.rs',
            'extension' => [
                'ident' => 'COM-' . \substr($id, 2),
                'identDescription' => 'Company profile',
                'identKind' => 'companyNumber',
                'isLegalEntity' => '1',
                'vatNo' => 'RS' . \substr(\md5($id), 0, 8),
            ],
            'id' => $id,
            'postalInfo' => [
                'address' => [
                    'city' => 'Novi Sad',
                    'countryCode' => 'RS',
                    'postalCode' => '21000',
                    'province' => 'NS',
                    'streets' => [ 'Bulevar Oslobodjenja 10' ],
                ],
                'name' => 'Company ' . \substr($id, 2, 6),
                'organization' => 'RNIDS Test Company',
                'type' => 'loc',
            ],
            'voice' => '+381.211111',
        ];
    }

    /**
     * @param non-empty-string $id
     *
     * @return array{
     *   id: non-empty-string,
     *   email: non-empty-string,
     *   voice: non-empty-string
     * }
     */
    public function updatePayload(string $id): array
    {
        if ('' === \trim($id)) {
            throw new \InvalidArgumentException('Contact update fixture id must be a non-empty string.');
        }

        return [
            'email' => 'updated-' . \strtolower($id) . '@example.rs',
            'id' => $id,
            'voice' => '+381.333333',
        ];
    }

    /**
     * @param non-empty-string $domain
     * @param non-empty-string $adminHandle
     * @param non-empty-string $techHandle
     *
     * @return array{
     *   name: non-empty-string,
     *   add: array{
     *     contacts: list<array{type: 'admin'|'tech', handle: non-empty-string}>
     *   },
     *   remove: array{
     *     contacts: list<array{type: 'admin'|'tech', handle: non-empty-string}>
     *   }
     * }
     */
    public function domainAdminTechChangePayload(string $domain, string $adminHandle, string $techHandle): array
    {
        return [
            'add' => [
                'contacts' => [
                    [ 'handle' => $adminHandle, 'type' => 'admin' ],
                    [ 'handle' => $techHandle, 'type' => 'tech' ],
                ],
            ],
            'name' => $domain,
            'remove' => [
                'contacts' => [
                    [ 'handle' => 'OLD-' . $adminHandle, 'type' => 'admin' ],
                    [ 'handle' => 'OLD-' . $techHandle, 'type' => 'tech' ],
                ],
            ],
        ];
    }

    /**
     * @param non-empty-string $domain
     * @param non-empty-string $registrantHandle
     *
     * @return array{
     *   name: non-empty-string,
     *   registrant: non-empty-string
     * }
     */
    public function domainRegistrantChangePayload(string $domain, string $registrantHandle): array
    {
        return [
            'name' => $domain,
            'registrant' => $registrantHandle,
        ];
    }
}
