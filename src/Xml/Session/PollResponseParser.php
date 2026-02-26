<?php

declare(strict_types=1);

namespace RNIDS\Xml\Session;

use RNIDS\Session\Dto\PollResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP poll command responses into typed DTOs.
 */
final class PollResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): PollResponse
    {
        $xpath = XmlParser::createXPath($xml);
        $queueCount = XmlParser::firstNodeInt($xpath, '/epp:epp/epp:response/epp:msgQ/@count');

        return new PollResponse(
            $metadata,
            $queueCount,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:msgQ/@id'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:msgQ/epp:qDate'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:msgQ/epp:msg'),
        );
    }
}
