<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainRenewResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP domain renew response XML into typed DTOs.
 */
final class DomainRenewResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): DomainRenewResponse
    {
        $xpath = XmlParser::createXPath($xml);

        return new DomainRenewResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:renData/domain:name'),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:renData/domain:exDate',
            ),
        );
    }
}
