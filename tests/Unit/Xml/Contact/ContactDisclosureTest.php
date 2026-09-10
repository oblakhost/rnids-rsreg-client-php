<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Contact;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Contact\ContactRequestFactory;
use RNIDS\Xml\Contact\ContactCreateRequestBuilder;
use RNIDS\Xml\Contact\ContactUpdateRequestBuilder;
use RNIDS\Xml\NamespaceRegistry;

#[Group('unit')]
final class ContactDisclosureTest extends TestCase
{
    /**
     * @return iterable<string, array{type: string, disclose: int}>
     */
    public static function postalTypes(): iterable
    {
        yield 'local enabled' => [ 'type' => 'loc', 'disclose' => 1 ];
        yield 'international disabled' => [ 'type' => 'int', 'disclose' => 0 ];
    }

    #[DataProvider('postalTypes')]
    public function testDisclosureIdentifiesThePostalInfoTypeOnCreateAndUpdate(string $type, int $disclose): void
    {
        $payload = [
            'disclose' => $disclose,
            'email' => 'person@example.rs',
            'id' => 'C-123',
            'postalInfo' => [
                'address' => [ 'streets' => [ 'Main 1' ], 'city' => 'Belgrade', 'countryCode' => 'RS' ],
                'name' => 'Person Example',
                'type' => $type,
            ],
        ];
        $factory = new ContactRequestFactory();
        $createXml = (new ContactCreateRequestBuilder())->build(
            $factory->createFromArray($payload),
            'CREATE-1',
        );
        $updateXml = (new ContactUpdateRequestBuilder())->build(
            $factory->updateFromArray($payload),
            'UPDATE-1',
        );

        $this->assertDisclosure($createXml, $type, $disclose);
        $this->assertDisclosure($updateXml, $type, $disclose);
    }

    public function testDisclosureOnlyUpdateDefaultsToLocalPostalData(): void
    {
        $request = (new ContactRequestFactory())->updateFromArray([ 'id' => 'C-123', 'disclose' => 1 ]);
        $xml = (new ContactUpdateRequestBuilder())->build($request, 'UPDATE-2');

        $this->assertDisclosure($xml, 'loc', 1);
    }

    private function assertDisclosure(string $xml, string $type, int $disclose): void
    {
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('contact', NamespaceRegistry::CONTACT);

        self::assertSame((string) $disclose, $xpath->evaluate('string(//contact:disclose/@flag)'));

        // RFC 5733 section 4: name/org/addr use intLocType with a required type attribute.
        foreach ([ 'name', 'org', 'addr' ] as $element) {
            self::assertSame(
                $type,
                $xpath->evaluate('string(//contact:disclose/contact:' . $element . '/@type)'),
            );
        }
    }
}
