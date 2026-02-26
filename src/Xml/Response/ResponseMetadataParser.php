<?php

declare(strict_types=1);

namespace RNIDS\Xml\Response;

use RNIDS\Xml\Parser\XmlParser;

/**
 * Parses shared EPP response metadata from raw XML.
 */
final class ResponseMetadataParser
{
    /**
     * Parses result status/message and transaction identifiers.
     *
     * For greeting frames that do not include a result code, this method maps
     * them to a synthetic successful metadata response.
     *
     * @throws \RNIDS\Exception\MalformedResponseException
     */
    public function parse(string $xml): ResponseMetadata
    {
        $xpath = XmlParser::createXPath($xml);
        $resultCode = XmlParser::firstNodeInt($xpath, '/epp:epp/epp:response/epp:result/@code');

        if (null === $resultCode) {
            $isGreeting = null !== XmlParser::firstNodeValue($xpath, '/epp:epp/epp:greeting/epp:svID');

            if ($isGreeting) {
                return new ResponseMetadata(1000, 'Greeting', null, null);
            }

            throw new \RNIDS\Exception\MalformedResponseException(
                'Unable to parse EPP result code from response XML.',
            );
        }

        $message = XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:result/epp:msg') ?? '';
        $clientTransactionId = XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:trID/epp:clTRID');
        $serverTransactionId = XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:trID/epp:svTRID');

        return new ResponseMetadata($resultCode, $message, $clientTransactionId, $serverTransactionId);
    }
}
