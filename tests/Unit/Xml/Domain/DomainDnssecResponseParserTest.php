<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Exception\MalformedResponseException;
use RNIDS\Xml\Domain\DomainDnssecResponseParser;
use RNIDS\Xml\Parser\XmlParser;

#[Group('unit')]
final class DomainDnssecResponseParserTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function invalidRecords(): iterable
    {
        yield 'missing numeric field' => [ '', \str_repeat('AB', 32) ];
        yield 'non-numeric algorithm' => [ 'not-an-algorithm', \str_repeat('AB', 32) ];
        yield 'unsupported algorithm' => [ '255', \str_repeat('AB', 32) ];
        yield 'bad digest' => [ '8', 'not-hex' ];
    }

    #[DataProvider('invalidRecords')]
    public function testMalformedDsDataFailsInsteadOfInventingARecord(string $algorithm, string $digest): void
    {
        $xpath = XmlParser::createXPath(
            '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"><response><extension>'
            . '<secDNS:infData xmlns:secDNS="urn:ietf:params:xml:ns:secDNS-1.1"><secDNS:dsData>'
            . '<secDNS:keyTag>12345</secDNS:keyTag><secDNS:alg>' . $algorithm . '</secDNS:alg>'
            . '<secDNS:digestType>2</secDNS:digestType><secDNS:digest>' . $digest . '</secDNS:digest>'
            . '</secDNS:dsData></secDNS:infData></extension></response></epp>',
        );
        $this->expectException(\RNIDS\Exception\MalformedResponseException::class);

        (new DomainDnssecResponseParser())->parse($xpath);
    }
}
