<?php

declare(strict_types=1);

namespace RNIDS\Xml\Contact;

use RNIDS\Contact\Dto\ContactCheckItem;
use RNIDS\Contact\Dto\ContactCheckResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

final class ContactCheckResponseParser
{
    public function parse(string $xml, ResponseMetadata $metadata): ContactCheckResponse
    {
        $xpath = XmlParser::createXPath($xml);
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/contact:chkData/contact:cd');

        if (false === $nodes) {
            return new ContactCheckResponse($metadata, []);
        }

        $items = [];

        foreach ($nodes as $node) {
            $item = $this->parseItemNode($xpath, $node);

            if (null === $item) {
                continue;
            }

            $items[] = $item;
        }

        return new ContactCheckResponse($metadata, $items);
    }

    private function parseItemNode(\DOMXPath $xpath, \DOMNode $node): ?ContactCheckItem
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $idNodes = $xpath->query('contact:id', $node);

        if (false === $idNodes) {
            return null;
        }

        $idNode = $idNodes->item(0);

        if (!$idNode instanceof \DOMElement) {
            return null;
        }

        $id = \trim($idNode->textContent);

        if ('' === $id) {
            return null;
        }

        return new ContactCheckItem(
            $id,
            '1' === $idNode->getAttribute('avail'),
            $this->parseReason($xpath, $node),
        );
    }

    private function parseReason(\DOMXPath $xpath, \DOMElement $contextNode): ?string
    {
        $reasonNodes = $xpath->query('contact:reason', $contextNode);

        if (false === $reasonNodes) {
            return null;
        }

        $reason = $reasonNodes->item(0)?->textContent;

        if (!\is_string($reason)) {
            return null;
        }

        $trimmedReason = \trim($reason);

        return '' === $trimmedReason ? null : $trimmedReason;
    }
}
