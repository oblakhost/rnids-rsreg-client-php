<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostInfoResponse;
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
            $this->parseAddressesByVersion($xpath, 'v4'),
            $this->parseAddressesByVersion($xpath, 'v6'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:clID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:crID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/host:infData/host:upID'),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/host:infData/host:crDate',
            ),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/host:infData/host:upDate',
            ),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/host:infData/host:trDate',
            ),
        );
    }

    /**
     * @return list<string>
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

    private function parseStatusNode(\DOMNode $node): ?string
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $value = \trim($node->getAttribute('s'));

        if ('' === $value) {
            return null;
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function parseAddressesByVersion(\DOMXPath $xpath, string $version): array
    {
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/host:infData/host:addr');

        if (false === $nodes || 0 === $nodes->length) {
            return [];
        }

        $addresses = [];

        foreach ($nodes as $node) {
            $address = $this->parseAddressNode($node, $version);

            if (null === $address) {
                continue;
            }

            $addresses[] = $address;
        }

        return $addresses;
    }

    private function parseAddressNode(\DOMNode $node, string $version): ?string
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $address = \trim($node->textContent);

        if ('' === $address) {
            return null;
        }

        $ipVersion = \trim($node->getAttribute('ip'));
        $normalizedVersion = '' === $ipVersion ? 'v4' : $ipVersion;

        if ($normalizedVersion !== $version) {
            return null;
        }

        return $address;
    }
}
