<?php

declare(strict_types=1);

namespace Tests\Unit\Xml;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\Dto as Domain;
use RNIDS\Host\Dto as Host;
use RNIDS\Xml\Domain as DomainXml;
use RNIDS\Xml\Host as HostXml;

#[Group('unit')]
final class IdnRequestEncodingTest extends TestCase
{
    #[DataProvider('domainCommands')]
    public function testDomainCommandsEncodeSerbianNamesAsAscii(object $builder, object $request): void
    {
        $xml = $builder->build($request, 'IDN-DOMAIN');
        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($xml));
        $names = $document->getElementsByTagNameNS('urn:ietf:params:xml:ns:domain-1.0', 'name');
        self::assertSame('xn--e1afmkfd.xn--90a3ac', $names->item(0)?->textContent);
    }

    public static function domainCommands(): iterable
    {
        $name = 'пример.срб';
        yield 'check' => [new DomainXml\DomainCheckRequestBuilder(), new Domain\DomainCheckRequest([$name])];
        yield 'info' => [new DomainXml\DomainInfoRequestBuilder(), new Domain\DomainInfoRequest($name)];
        yield 'register' => [new DomainXml\DomainRegisterRequestBuilder(), new Domain\DomainRegisterRequest(
            $name, 1, 'y', [], 'REG-1', [], 'password', null,
        )];
        yield 'renew' => [new DomainXml\DomainRenewRequestBuilder(), new Domain\DomainRenewRequest(
            $name, '2027-09-11', 1,
        )];
        yield 'update' => [new DomainXml\DomainUpdateRequestBuilder(), new Domain\DomainUpdateRequest($name)];
        yield 'delete' => [new DomainXml\DomainDeleteRequestBuilder(), new Domain\DomainDeleteRequest($name)];
        yield 'transfer' => [new DomainXml\DomainTransferRequestBuilder(), new Domain\DomainTransferRequest(
            'request', $name, null,
        )];
    }

    #[DataProvider('hostCommands')]
    public function testHostCommandsEncodeSerbianNamesAsAscii(object $builder, object $request): void
    {
        $xml = $builder->build($request, 'IDN-HOST');
        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($xml));
        $names = $document->getElementsByTagNameNS('urn:ietf:params:xml:ns:host-1.0', 'name');
        self::assertSame('ns1.xn--e1afmkfd.xn--90a3ac', $names->item(0)?->textContent);
        if ($request instanceof Host\HostUpdateRequest) {
            self::assertSame('ns2.xn--e1afmkfd.xn--90a3ac', $names->item(1)?->textContent);
        }
    }

    public static function hostCommands(): iterable
    {
        $name = 'ns1.пример.срб';
        yield 'check' => [new HostXml\HostCheckRequestBuilder(), new Host\HostCheckRequest([$name])];
        yield 'info' => [new HostXml\HostInfoRequestBuilder(), new Host\HostInfoRequest($name)];
        yield 'create' => [new HostXml\HostCreateRequestBuilder(), new Host\HostCreateRequest($name, [])];
        yield 'update' => [new HostXml\HostUpdateRequestBuilder(), new Host\HostUpdateRequest(
            $name, null, null, 'ns2.пример.срб',
        )];
        yield 'delete' => [new HostXml\HostDeleteRequestBuilder(), new Host\HostDeleteRequest($name)];
    }

    public function testDelegationEncodesBothObjectAndGlueNameservers(): void
    {
        $builder = new DomainXml\DomainNameserverXmlBuilder();
        self::assertSame(
            '<domain:ns><domain:hostObj>ns1.xn--e1afmkfd.xn--90a3ac</domain:hostObj></domain:ns>',
            $builder->build([new Domain\DomainRegisterNameserver('ns1.пример.срб')]),
        );
        self::assertStringContainsString(
            '<domain:hostName>ns1.xn--e1afmkfd.xn--90a3ac</domain:hostName>',
            $builder->build([new Domain\DomainRegisterNameserver('ns1.пример.срб', ['192.0.2.1'])]),
        );
    }

    public function testIdnEncodingPreservesOtherUnicodeText(): void
    {
        $xml = (new DomainXml\DomainRegisterRequestBuilder())->build(new Domain\DomainRegisterRequest(
            'пример.срб', 1, 'y', [], 'REG-1', [], 'Лозинка<&>', new Domain\DomainExtension('Напомена', null, null, null, null),
        ), 'IDN-TEXT');
        self::assertStringContainsString('<domain:pw>Лозинка&lt;&amp;&gt;</domain:pw>', $xml);
        self::assertStringContainsString('<domainExt:remark>Напомена</domainExt:remark>', $xml);
    }

    public function testInvalidInternationalizedNameFailsBeforeCommandConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain or host name cannot be encoded as a valid IDN.');
        (new DomainXml\DomainCheckRequestBuilder())->build(
            new Domain\DomainCheckRequest(['пример..срб']),
            'INVALID-IDN',
        );
    }
}
