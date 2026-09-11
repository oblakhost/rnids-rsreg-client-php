<?php

declare(strict_types=1);

namespace Tests\Unit\Xml;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RNIDS\Exception\MalformedResponseException;
use RNIDS\Xml\Contact\ContactCheckResponseParser;
use RNIDS\Xml\Domain\DomainCheckResponseParser;
use RNIDS\Xml\Host\HostCheckResponseParser;
use RNIDS\Xml\Response\ResponseMetadata;

final class CheckAvailabilityBooleanTest extends TestCase
{
    #[DataProvider('parsers')]
    public function testAvailabilityAcceptsEveryXmlBooleanForm(object $parser, string $type, string $field): void
    {
        $items = '';
        foreach (['1', 'true', '0', 'false', ' true '] as $value) {
            $items .= '<' . $type . ':cd><' . $type . ':' . $field . ' avail="' . $value . '">'
                . 'fixture.rs</' . $type . ':' . $field . '></' . $type . ':cd>';
        }
        $response = $parser->parse($this->responseXml($type, $items), new ResponseMetadata(1000, 'OK', 'C', 'S'));
        self::assertSame(
            [true, true, false, false, true],
            \array_map(static fn(object $item): bool => $item->available, $response->items),
        );
    }

    #[DataProvider('invalidAvailability')]
    public function testInvalidOrMissingAvailabilityRaisesAProtocolError(
        object $parser,
        string $type,
        string $field,
        ?string $availability,
    ): void {
        $attribute = null === $availability ? '' : ' avail="' . $availability . '"';
        $item = '<' . $type . ':cd><' . $type . ':' . $field . $attribute . '>fixture.rs</'
            . $type . ':' . $field . '></' . $type . ':cd>';
        $this->expectException(MalformedResponseException::class);
        $this->expectExceptionMessage('EPP response attribute "avail" must be an XML boolean.');
        $parser->parse($this->responseXml($type, $item), new ResponseMetadata(1000, 'OK', 'C', 'S'));
    }

    public static function parsers(): iterable
    {
        yield 'domain' => [new DomainCheckResponseParser(), 'domain', 'name'];
        yield 'contact' => [new ContactCheckResponseParser(), 'contact', 'id'];
        yield 'host' => [new HostCheckResponseParser(), 'host', 'name'];
    }

    public static function invalidAvailability(): iterable
    {
        foreach (self::parsers() as $type => $row) {
            foreach ([null, 'yes', 'TRUE'] as $index => $value) {
                yield $type . '-' . $index => [...$row, $value];
            }
        }
    }

    private function responseXml(string $type, string $items): string
    {
        return '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"><response><resData><'
            . $type . ':chkData xmlns:' . $type . '="urn:ietf:params:xml:ns:' . $type . '-1.0">'
            . $items . '</' . $type . ':chkData></resData></response></epp>';
    }
}
