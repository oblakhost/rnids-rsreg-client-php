<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostCreateResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP host create response XML into typed DTOs.
 */
final class HostCreateResponseParser
{
    /**
     * Parses an EPP host create response XML payload.
     */
    public function parse(string $xml, ResponseMetadata $metadata): HostCreateResponse
    {
        $xpath = XmlParser::createXPath($xml);

        return new HostCreateResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:creData/host:name'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:creData/host:crDate'),
        );
    }
}
