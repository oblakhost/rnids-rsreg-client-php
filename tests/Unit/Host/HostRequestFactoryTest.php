<?php

declare(strict_types=1);

namespace Tests\Unit\Host;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Host\HostRequestFactory;

#[Group('unit')]
final class HostRequestFactoryTest extends TestCase
{
    public function testCheckFromArrayBuildsRequestForValidNames(): void
    {
        $factory = new HostRequestFactory();

        $request = $factory->checkFromArray([ 'names' => [ 'ns1.example.rs', 'ns2.example.rs' ] ]);

        self::assertSame([ 'ns1.example.rs', 'ns2.example.rs' ], $request->names);
    }

    #[DataProvider('invalidCheckPayloadProvider')]
    public function testCheckFromArrayRejectsInvalidPayload(array $payload, string $errorMessage): void
    {
        $factory = new HostRequestFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($errorMessage);

        $factory->checkFromArray($payload);
    }

    /**
     * @return iterable<string, array{payload: array<string, mixed>, errorMessage: string}>
     */
    public static function invalidCheckPayloadProvider(): iterable
    {
        yield 'missing names' => [
            'payload' => [],
            'errorMessage' => 'Host check request key "names" must be a non-empty list of strings.',
        ];

        yield 'names include empty item' => [
            'payload' => [ 'names' => [ 'ns1.example.rs', '' ] ],
            'errorMessage' => 'Host check request key "names" must contain only non-empty strings.',
        ];
    }

    public function testCreateFromArrayBuildsRequestWithDefaultIpVersion(): void
    {
        $factory = new HostRequestFactory();

        $request = $factory->createFromArray([
            'addresses' => [
                [ 'address' => '192.0.2.1' ],
                [ 'address' => '2001:db8::1', 'ipVersion' => 'v6' ],
            ],
            'name' => 'ns1.example.rs',
        ]);

        self::assertSame('ns1.example.rs', $request->name);
        self::assertCount(2, $request->addresses);
        self::assertSame('192.0.2.1', $request->addresses[0]->address);
        self::assertSame('v4', $request->addresses[0]->ipVersion);
        self::assertSame('v6', $request->addresses[1]->ipVersion);
    }

    #[DataProvider('invalidCreatePayloadProvider')]
    public function testCreateFromArrayRejectsInvalidPayload(array $payload, string $errorMessage): void
    {
        $factory = new HostRequestFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($errorMessage);

        $factory->createFromArray($payload);
    }

    /**
     * @return iterable<string, array{payload: array<string, mixed>, errorMessage: string}>
     */
    public static function invalidCreatePayloadProvider(): iterable
    {
        yield 'missing name' => [
            'payload' => [ 'addresses' => [] ],
            'errorMessage' => 'Host create request key "name" must be a non-empty string.',
        ];

        yield 'addresses must be list' => [
            'payload' => [ 'addresses' => 'bad', 'name' => 'ns1.example.rs' ],
            'errorMessage' => 'Host create request key "addresses" must be a list.',
        ];

        yield 'address item must be array' => [
            'payload' => [ 'addresses' => [ 'bad' ], 'name' => 'ns1.example.rs' ],
            'errorMessage' => 'Host addresses address at index 0 must be an array.',
        ];

        yield 'address value required' => [
            'payload' => [
                'addresses' => [ [ 'address' => '', 'ipVersion' => 'v4' ] ],
                'name' => 'ns1.example.rs',
            ],
            'errorMessage' => 'Host addresses address at index 0 must include non-empty "address".',
        ];

        yield 'ip version must be known' => [
            'payload' => [
                'addresses' => [ [ 'address' => '192.0.2.1', 'ipVersion' => 'v5' ] ],
                'name' => 'ns1.example.rs',
            ],
            'errorMessage' => 'Host addresses address at index 0 has invalid "ipVersion" (allowed: v4, v6).',
        ];
    }

    public function testUpdateFromArrayBuildsRequestForAddRemoveAndRename(): void
    {
        $factory = new HostRequestFactory();

        $request = $factory->updateFromArray([
            'add' => [
                'addresses' => [ [ 'address' => '192.0.2.2', 'ipVersion' => 'v4' ] ],
                'statuses' => [ 'ok' ],
            ],
            'name' => 'ns1.example.rs',
            'newName' => 'ns2.example.rs',
            'remove' => [
                'statuses' => [ 'clientHold' ],
            ],
        ]);

        self::assertSame('ns1.example.rs', $request->name);
        self::assertNotNull($request->add);
        self::assertNotNull($request->remove);
        self::assertSame('ns2.example.rs', $request->newName);
        self::assertSame('192.0.2.2', $request->add->addresses[0]->address);
        self::assertSame([ 'ok' ], $request->add->statuses);
        self::assertSame([ 'clientHold' ], $request->remove->statuses);
    }

    #[DataProvider('invalidUpdatePayloadProvider')]
    public function testUpdateFromArrayRejectsInvalidPayload(array $payload, string $errorMessage): void
    {
        $factory = new HostRequestFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($errorMessage);

        $factory->updateFromArray($payload);
    }

    /**
     * @return iterable<string, array{payload: array<string, mixed>, errorMessage: string}>
     */
    public static function invalidUpdatePayloadProvider(): iterable
    {
        yield 'no mutations provided' => [
            'payload' => [ 'name' => 'ns1.example.rs' ],
            'errorMessage' => 'Host update request must include at least one of "add", "remove", or "newName".',
        ];

        yield 'missing name' => [
            'payload' => [
                'add' => [
                    'statuses' => [ 'ok' ],
                ],
            ],
            'errorMessage' => 'Host update request key "name" must be a non-empty string.',
        ];

        yield 'newName must be non-empty when provided' => [
            'payload' => [
                'name' => 'ns1.example.rs',
                'newName' => '  ',
            ],
            'errorMessage' => 'Host request key "newName" must be a non-empty string when provided.',
        ];

        yield 'update section must be array' => [
            'payload' => [
                'add' => 'bad',
                'name' => 'ns1.example.rs',
            ],
            'errorMessage' => 'Host update request key "add" must be an array.',
        ];

        yield 'update addresses must be list' => [
            'payload' => [
                'add' => [ 'addresses' => 'bad' ],
                'name' => 'ns1.example.rs',
            ],
            'errorMessage' => 'Host update key "addresses" must be a list.',
        ];

        yield 'update statuses must be list' => [
            'payload' => [
                'add' => [ 'statuses' => 'bad' ],
                'name' => 'ns1.example.rs',
            ],
            'errorMessage' => 'Host update key "statuses" must be a list.',
        ];

        yield 'update section requires at least one address or status' => [
            'payload' => [
                'add' => [],
                'name' => 'ns1.example.rs',
            ],
            'errorMessage' => 'Host update request key "add" must contain at least one address or status.',
        ];

        yield 'status items must be non-empty strings' => [
            'payload' => [
                'name' => 'ns1.example.rs',
                'remove' => [
                    'statuses' => [ 'ok', '' ],
                ],
            ],
            'errorMessage' => 'Host update status at index 1 must be a non-empty string.',
        ];
    }
}
