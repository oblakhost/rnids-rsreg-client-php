<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactRequestFactory;
use RNIDS\Contact\Dto\ContactAddress;
use RNIDS\Contact\Dto\ContactExtension;
use RNIDS\Contact\Dto\ContactPostalInfo;
use RNIDS\Contact\Dto\ContactUpdateRequest;
use RNIDS\Xml\Contact\ContactUpdateRequestBuilder;
use RNIDS\Xml\NamespaceRegistry;

#[Group('unit')]
final class ContactUpdateClearingTest extends TestCase
{
    /**
     * @return iterable<string, array{field: string, element: string}>
     */
    public static function communicationFields(): iterable
    {
        yield 'fax' => [ 'field' => 'fax', 'element' => 'contact:fax' ];
        yield 'authorization password' => [ 'field' => 'authInfo', 'element' => 'contact:authInfo/contact:pw' ];
    }

    /**
     * @return iterable<string, array{field: string}>
     */
    public static function typedExtensionFields(): iterable
    {
        yield 'date' => [ 'field' => 'identExpiry' ];
        yield 'boolean' => [ 'field' => 'isLegalEntity' ];
        yield 'enum' => [ 'field' => 'identKind' ];
    }

    #[DataProvider('communicationFields')]
    public function testUpdateEmitsTheRequestedEmptyCommunicationElement(string $field, string $element): void
    {
        $request = (new ContactRequestFactory())->updateFromArray([ 'id' => 'LEGACY-42', $field => '' ]);
        $xml = (new ContactUpdateRequestBuilder())->build($request, 'CLEAR-1');
        $xpath = $this->xpath($xml);
        $nodes = $xpath->query('//contact:chg/' . $element);

        self::assertSame(1, $nodes->length);
        self::assertSame('', $nodes->item(0)?->textContent);
        self::assertSame('LEGACY-42', $xpath->evaluate('string(//contact:update/contact:id)'));
    }

    #[DataProvider('communicationFields')]
    public function testNullLeavesOptionalCommunicationFieldUnchanged(string $field, string $element): void
    {
        $request = (new ContactRequestFactory())->updateFromArray([
            $field => null,
            'email' => 'updated@example.rs',
            'id' => 'LEGACY-42',
        ]);
        $xml = (new ContactUpdateRequestBuilder())->build($request, 'LEAVE-1');

        self::assertSame(0, $this->xpath($xml)->query('//contact:chg/' . $element)->length);
    }

    public function testExplicitDtoEmitsEmptyElementsWithoutApplyingRegistryUpdatePolicy(): void
    {
        $request = new ContactUpdateRequest(
            id: 'LEGACY-42',
            addStatuses: [],
            removeStatuses: [],
            postalInfo: new ContactPostalInfo(
                'loc',
                'Person Example',
                '',
                new ContactAddress(['Main 1'], 'Belgrade', 'RS', '', ''),
            ),
            voice: '',
            fax: null,
            email: null,
            authInfo: null,
            disclose: null,
            extension: new ContactExtension(null, '', null, null, null, ''),
        );
        $xml = (new ContactUpdateRequestBuilder())->build($request, 'CLEAR-2');
        $xpath = $this->xpath($xml);

        $elements = [ 'contact:voice', 'contact:org', 'contact:sp', 'contact:pc', 'contactExt:identDescription', 'contactExt:vatNo' ];

        foreach ($elements as $element) {
            $nodes = $xpath->query('//' . $element);
            self::assertSame(1, $nodes->length);
            self::assertSame('', $nodes->item(0)?->textContent);
        }
    }

    public function testCreateStillRejectsEmptyOptionalCommunicationFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ContactRequestFactory())->createFromArray([
            'email' => 'person@example.rs',
            'fax' => '',
            'voice' => '+381.111111',
            'postalInfo' => [
                'address' => [ 'streets' => [ 'Main 1' ], 'city' => 'Belgrade', 'countryCode' => 'RS' ],
                'name' => 'Person Example',
            ],
        ]);
    }

    public function testUpdateRejectsAnEmptyRequiredEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ContactRequestFactory())->updateFromArray([ 'id' => 'LEGACY-42', 'email' => '' ]);
    }

    #[DataProvider('typedExtensionFields')]
    public function testUpdateDoesNotClearTypedExtensionValuesWithAnInvalidEmptyElement(string $field): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ContactRequestFactory())->updateFromArray(
            [ 'id' => 'LEGACY-42', 'extension' => [ $field => '' ] ],
        );
    }

    public function testEmptyExtensionDoesNotCountAsAChange(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ContactRequestFactory())->updateFromArray([ 'id' => 'LEGACY-42', 'extension' => [] ]);
    }

    public function testUpdateSendsOnlySuppliedIdentificationFields(): void
    {
        $request = (new ContactRequestFactory())->updateFromArray([
            'extension' => [ 'ident' => '12345' ],
            'id' => 'LEGACY-42',
        ]);
        $xml = (new ContactUpdateRequestBuilder())->build($request, 'IDENT-1');
        $xpath = $this->xpath($xml);

        self::assertSame('12345', $xpath->evaluate('string(//contactExt:ident)'));
        self::assertSame(0, $xpath->query('//contactExt:identDescription')->length);
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
