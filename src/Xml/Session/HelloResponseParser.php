<?php

declare(strict_types=1);

namespace RNIDS\Xml\Session;

use RNIDS\Session\Dto\HelloResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP greeting frames into typed hello response DTOs.
 */
final class HelloResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): HelloResponse
    {
        $xpath = XmlParser::createXPath($xml);

        return new HelloResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:greeting/epp:svID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:greeting/epp:svDate'),
            XmlParser::nodeValues($xpath, '/epp:epp/epp:greeting/epp:svcMenu/epp:version'),
            XmlParser::nodeValues($xpath, '/epp:epp/epp:greeting/epp:svcMenu/epp:lang'),
            XmlParser::nodeValues($xpath, '/epp:epp/epp:greeting/epp:svcMenu/epp:objURI'),
            XmlParser::nodeValues($xpath, '/epp:epp/epp:greeting/epp:svcMenu/epp:svcExtension/epp:extURI'),
        );
    }
}
