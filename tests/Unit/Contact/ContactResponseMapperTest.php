<?php

declare(strict_types=1);

namespace Tests\Unit\Contact;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactResponseMapper;
use RNIDS\Contact\Dto\ContactAddress;
use RNIDS\Contact\Dto\ContactCheckItem;
use RNIDS\Contact\Dto\ContactCheckResponse;
use RNIDS\Contact\Dto\ContactCreateResponse;
use RNIDS\Contact\Dto\ContactInfoResponse;
use RNIDS\Contact\Dto\ContactPostalInfo;
use RNIDS\Xml\Response\ResponseMetadata;

#[Group('unit')]
final class ContactResponseMapperTest extends TestCase
{
    public function testMapCheckResponse(): void
    {
        $mapper = new ContactResponseMapper();
        $metadata = new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1');

        $response = new ContactCheckResponse(
            $metadata,
            [ new ContactCheckItem('C-100', true, null) ],
        );

        self::assertSame([
            [
                'available' => true,
                'id' => 'C-100',
                'reason' => null,
            ],
        ], $mapper->mapCheckResponse($response));
    }

    public function testMapInfoResponse(): void
    {
        $mapper = new ContactResponseMapper();
        $metadata = new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1');

        $response = new ContactInfoResponse(
            $metadata,
            'C-300',
            'C300-RS',
            [ 'ok' ],
            new ContactPostalInfo(
                ContactPostalInfo::TYPE_LOC,
                'Person Example',
                null,
                new ContactAddress([ 'Main 1' ], 'Belgrade', 'RS', null, null),
            ),
            '+381.11',
            null,
            'person@example.rs',
            'CID',
            'CCID',
            'UCID',
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
            new \DateTimeImmutable('2025-01-01T00:00:00Z'),
            null,
            1,
            '12345',
            null,
            null,
            null,
            false,
            null,
        );

        $mapped = $mapper->mapInfoResponse($response);

        self::assertSame('C-300', $mapped['id']);
        self::assertSame('ok', $mapped['statuses'][0]);
        self::assertSame('loc', $mapped['postalType']);
        self::assertSame('Person Example', $mapped['postalName']);
        self::assertNull($mapped['postalOrganization']);
        self::assertSame('Main 1', $mapped['postalStreet1']);
        self::assertNull($mapped['postalStreet2']);
        self::assertNull($mapped['postalStreet3']);
        self::assertSame('Belgrade', $mapped['postalCity']);
        self::assertSame('RS', $mapped['postalCountryCode']);
        self::assertNull($mapped['postalProvince']);
        self::assertNull($mapped['postalPostalCode']);
        self::assertSame('Belgrade', $mapped['postalInfo']['address']['city']);
        self::assertSame('12345', $mapped['ident']);
    }

    public function testMapCreateAndEmptyResponses(): void
    {
        $mapper = new ContactResponseMapper();
        $metadata = new ResponseMetadata(1000, 'OK', 'CL-1', 'SV-1');

        $create = $mapper->mapCreateResponse(
            new ContactCreateResponse($metadata, 'C-200', new \DateTimeImmutable('2026-03-01T00:00:00.0Z')),
        );
        self::assertSame('C-200', $create['id']);
        self::assertInstanceOf(\DateTimeImmutable::class, $create['createDate']);

        self::assertSame([], $mapper->mapEmptyResponse());
    }
}
