<?php

declare(strict_types=1);

namespace Tests\Unit\Contact;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactService;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use RNIDS\Xml\NamespaceRegistry;
use Tests\Support\SessionPeerTransport;

/**
 * @phpstan-type PostalInput array{
 *   name: string,
 *   organization?: string|null,
 *   address: array{
 *     streets: list<string>, city: string, countryCode: string,
 *     province?: string|null, postalCode?: string|null
 *   }
 * }
 * @phpstan-type UpdateInput array{id: string, voice?: string, postalInfo?: PostalInput}
 */
final class ContactUpdatePolicyTest extends TestCase
{
    /** @return iterable<string, array{payload: UpdateInput, field: string}> */
    public static function unsupportedClears(): iterable
    {
        yield 'mandatory phone' => [
            'payload' => ['id' => 'C-400', 'voice' => ''],
            'field' => 'voice',
        ];

        $postalInfo = self::postalInput();
        $postalInfo['organization'] = '';
        yield 'organization' => [
            'payload' => ['id' => 'C-400', 'postalInfo' => $postalInfo],
            'field' => 'organization',
        ];

        foreach (['province', 'postalCode'] as $field) {
            $postalInfo = self::postalInput();
            $postalInfo['address'][$field] = '';
            yield $field => [
                'payload' => ['id' => 'C-400', 'postalInfo' => $postalInfo],
                'field' => $field,
            ];
        }
    }

    /** @param UpdateInput $payload */
    #[DataProvider('unsupportedClears')]
    public function testUnsupportedClearIsRejectedBeforeSendingACommand(array $payload, string $field): void
    {
        ['service' => $service, 'peer' => $peer] = $this->service();

        try {
            $service->update($payload);
            self::fail('An unsupported contact field clear was sent to the registry.');
        } catch (\InvalidArgumentException $error) {
            self::assertStringContainsString('"' . $field . '"', $error->getMessage());
            self::assertStringContainsString('non-empty', $error->getMessage());
        }

        self::assertSame([], $peer->requests);
    }

    /** @return iterable<string, array{name: string, legalEntity: string}> */
    public static function contactKinds(): iterable
    {
        yield 'natural person' => ['name' => 'Person Example', 'legalEntity' => '0'];
        yield 'legal entity without a person name' => ['name' => '', 'legalEntity' => '1'];
    }

    #[DataProvider('contactKinds')]
    public function testNonEmptyReplacementsPreserveContactKindRules(string $name, string $legalEntity): void
    {
        ['service' => $service, 'peer' => $peer] = $this->service();
        $postalInfo = self::postalInput();
        $postalInfo['name'] = $name;
        $postalInfo['organization'] = 'Example Company';
        $postalInfo['address']['province'] = 'Vojvodina';
        $postalInfo['address']['postalCode'] = '21000';

        self::assertSame([], $service->update([
            'id' => 'C-400',
            'voice' => '+381.211111',
            'postalInfo' => $postalInfo,
            'extension' => ['isLegalEntity' => $legalEntity],
        ]));

        $xpath = $this->xpath($peer->requests[0]);
        self::assertSame('+381.211111', $xpath->evaluate('string(//contact:chg/contact:voice)'));
        self::assertSame($name, $xpath->evaluate('string(//contact:postalInfo/contact:name)'));
        self::assertSame('Example Company', $xpath->evaluate('string(//contact:postalInfo/contact:org)'));
        self::assertSame('Vojvodina', $xpath->evaluate('string(//contact:addr/contact:sp)'));
        self::assertSame('21000', $xpath->evaluate('string(//contact:addr/contact:pc)'));
        self::assertSame($legalEntity, $xpath->evaluate('string(//contactExt:isLegalEntity)'));
    }

    /** @return iterable<string, array{explicitNull: bool}> */
    public static function unchangedInputs(): iterable
    {
        yield 'omitted' => ['explicitNull' => false];
        yield 'explicit null' => ['explicitNull' => true];
    }

    #[DataProvider('unchangedInputs')]
    public function testOmittedAndNullValuesLeaveExistingFieldsUnchanged(bool $explicitNull): void
    {
        ['service' => $service, 'peer' => $peer] = $this->service();
        $payload = ['id' => 'C-400', 'postalInfo' => self::postalInput()];

        if ($explicitNull) {
            $payload['voice'] = null;
            $payload['postalInfo']['organization'] = null;
            $payload['postalInfo']['address']['province'] = null;
            $payload['postalInfo']['address']['postalCode'] = null;
        }

        self::assertSame([], $service->update($payload));

        $xpath = $this->xpath($peer->requests[0]);
        self::assertSame('Person Example', $xpath->evaluate('string(//contact:postalInfo/contact:name)'));
        self::assertSame(0, $xpath->query('//contact:voice | //contact:org | //contact:sp | //contact:pc')->length);
    }

    public function testSupportedEmptyStringsStillReachTheRegistry(): void
    {
        ['service' => $service, 'peer' => $peer] = $this->service();

        self::assertSame([], $service->update([
            'id' => 'C-400',
            'fax' => '',
            'authInfo' => '',
            'extension' => ['ident' => '', 'identDescription' => '', 'vatNo' => ''],
        ]));

        $xpath = $this->xpath($peer->requests[0]);

        foreach (['contact:fax', 'contact:authInfo/contact:pw', 'contactExt:ident', 'contactExt:identDescription', 'contactExt:vatNo'] as $element) {
            $nodes = $xpath->query('//' . $element);
            self::assertSame(1, $nodes->length);
            self::assertSame('', $nodes->item(0)->textContent);
        }
    }

    /** @return array{service: ContactService, peer: SessionPeerTransport} */
    private function service(): array
    {
        $peer = new SessionPeerTransport(1000, false);
        $peer->connect();
        $peer->nextResponse = SessionPeerTransport::response(1000, 'CONTACT-UPDATE-00000001');

        return [
            'peer' => $peer,
            'service' => new ContactService($peer, null, new IncrementalClTridGenerator('CONTACT-UPDATE')),
        ];
    }

    /** @return PostalInput */
    private static function postalInput(): array
    {
        return [
            'name' => 'Person Example',
            'address' => [
                'streets' => ['Main 1'],
                'city' => 'Belgrade',
                'countryCode' => 'RS',
            ],
        ];
    }

    private function xpath(string $xml): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('contact', NamespaceRegistry::CONTACT);
        $xpath->registerNamespace('contactExt', NamespaceRegistry::RNIDS_CONTACT_EXT);

        return $xpath;
    }
}
