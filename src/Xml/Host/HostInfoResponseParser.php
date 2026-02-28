<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostAddress;
use RNIDS\Host\Dto\HostInfoResponse;
use RNIDS\Host\Dto\HostStatus;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP host info response XML into typed DTOs.
 */
final class HostInfoResponseParser
{
    /**
     * Parses an EPP host info response XML payload.
     */
    public function parse(string $xml, ResponseMetadata $metadata): HostInfoResponse
    {
        $xpath = XmlParser::createXPath($xml);

        return new HostInfoResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:name'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:roid'),
            $this->parseStatuses($xpath),
            $this->parseAddresses($xpath),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:clID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:crID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:upID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:crDate'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:upDate'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:trDate'),
        );
    }

    /**
     * @return list<HostStatus>
     */
    private function parseStatuses(\DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/host:infData/host:status');

        if (false === $nodes || 0 === $nodes->length) {
            return [];
        }

        $statuses = [];

        foreach ($nodes as $node) {
            $status = $this->parseStatusNode($node);

            if (null === $status) {
                continue;
            }

            $statuses[] = $status;
        }

        return $statuses;
    }

    private function parseStatusNode(\DOMNode $node): ?HostStatus
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $value = \trim($node->getAttribute('s'));

        if ('' === $value) {
            return null;
        }

        $description = \trim($node->textContent);

        return new HostStatus($value, '' === $description ? null : $description);
    }

    /**
     * @return list<HostAddress>
     */
    private function parseAddresses(\DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/host:infData/host:addr');

        if (false === $nodes || 0 === $nodes->length) {
            return [];
        }

        $addresses = [];

        foreach ($nodes as $node) {
            $address = $this->parseAddressNode($node);

            if (null === $address) {
                continue;
            }

            $addresses[] = $address;
        }

        return $addresses;
    }

    private function parseAddressNode(\DOMNode $node): ?HostAddress
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $address = \trim($node->textContent);

        if ('' === $address) {
            return null;
        }

        $ipVersion = \trim($node->getAttribute('ip'));

        return new HostAddress($address, '' === $ipVersion ? 'v4' : $ipVersion);
    }
}
