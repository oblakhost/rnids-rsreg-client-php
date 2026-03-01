<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainTransferResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP domain transfer response XML into typed DTOs.
 */
final class DomainTransferResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): DomainTransferResponse
    {
        $xpath = XmlParser::createXPath($xml);

        return new DomainTransferResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:trnData/domain:name'),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:trnData/domain:trStatus',
            ),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:trnData/domain:reID'),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:trnData/domain:reDate',
            ),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:trnData/domain:acID'),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:trnData/domain:acDate',
            ),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:trnData/domain:exDate',
            ),
        );
    }
}
