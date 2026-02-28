<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostCheckItem;
use RNIDS\Host\Dto\HostCheckResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP host check response XML into typed DTOs.
 */
final class HostCheckResponseParser
{
    public function parse(string $xml, ResponseMetadata $metadata): HostCheckResponse
    {
        $xpath = XmlParser::createXPath($xml);
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/host:chkData/host:cd');

        if (false === $nodes) {
            return new HostCheckResponse($metadata, []);
        }

        $items = [];

        foreach ($nodes as $node) {
            $item = $this->parseItem($xpath, $node);

            if (null === $item) {
                continue;
            }

            $items[] = $item;
        }

        return new HostCheckResponse($metadata, $items);
    }

    private function parseItem(\DOMXPath $xpath, \DOMNode $node): ?HostCheckItem
    {
        $nameNode = $this->queryNameNode($xpath, $node);

        if (!$nameNode instanceof \DOMElement) {
            return null;
        }

        $name = \trim($nameNode->textContent);

        if ('' === $name) {
            return null;
        }

        $reason = $this->queryReason($xpath, $node);

        return new HostCheckItem(
            $name,
            '1' === $nameNode->getAttribute('avail'),
            $reason,
        );
    }

    private function queryNameNode(\DOMXPath $xpath, \DOMNode $node): ?\DOMNode
    {
        $nameNodes = $xpath->query('host:name', $node);

        return false !== $nameNodes ? $nameNodes->item(0) : null;
    }

    private function queryReason(\DOMXPath $xpath, \DOMNode $node): ?string
    {
        $reasonNodes = $xpath->query('host:reason', $node);
        $reason = false !== $reasonNodes ? \trim((string) $reasonNodes->item(0)?->textContent) : '';

        return '' === $reason ? null : $reason;
    }
}
