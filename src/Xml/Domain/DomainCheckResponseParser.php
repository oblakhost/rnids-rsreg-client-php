<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainCheckItem;
use RNIDS\Domain\Dto\DomainCheckResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP domain check response XML into typed DTOs.
 */
final class DomainCheckResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): DomainCheckResponse
    {
        return new DomainCheckResponse($metadata, $this->parseItems($xml));
    }

    /**
     * @return list<DomainCheckItem>
     */
    private function parseItems(string $xml): array
    {
        $xpath = XmlParser::createXPath($xml);
        $cdNodes = $this->queryCheckNodes($xpath);

        if (false === $cdNodes) {
            return [];
        }

        $items = [];

        foreach ($cdNodes as $cdNode) {
            $item = $this->parseCheckNode($xpath, $cdNode);

            if (null === $item) {
                continue;
            }

            $items[] = $item;
        }

        return $items;
    }

    private function queryCheckNodes(\DOMXPath $xpath): \DOMNodeList|false
    {
        return $xpath->query('/epp:epp/epp:response/epp:resData/domain:chkData/domain:cd');
    }

    private function parseCheckNode(\DOMXPath $xpath, \DOMNode $cdNode): ?DomainCheckItem
    {
        $nameNode = $this->queryNameNode($xpath, $cdNode);

        if (!$nameNode instanceof \DOMElement) {
            return null;
        }

        $name = \trim($nameNode->textContent);

        if ('' === $name) {
            return null;
        }

        return new DomainCheckItem(
            $name,
            '1' === $nameNode->getAttribute('avail'),
            $this->queryReason($xpath, $cdNode),
        );
    }

    private function queryNameNode(\DOMXPath $xpath, \DOMNode $cdNode): ?\DOMNode
    {
        $nameNodes = $xpath->query('domain:name', $cdNode);

        return false !== $nameNodes ? $nameNodes->item(0) : null;
    }

    private function queryReason(\DOMXPath $xpath, \DOMNode $cdNode): ?string
    {
        $reasonNodes = $xpath->query('domain:reason', $cdNode);
        $reason = false !== $reasonNodes ? $reasonNodes->item(0)?->textContent : null;

        if (null === $reason) {
            return null;
        }

        $trimmed = \trim($reason);

        return '' === $trimmed ? null : $trimmed;
    }
}
