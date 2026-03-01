<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainRegisterResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP domain register response XML into typed DTOs.
 */
final class DomainRegisterResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): DomainRegisterResponse
    {
        $xpath = XmlParser::createXPath($xml);

        return new DomainRegisterResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:creData/domain:name'),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:creData/domain:crDate',
            ),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:creData/domain:exDate',
            ),
        );
    }
}
