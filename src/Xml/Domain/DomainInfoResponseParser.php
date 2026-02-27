<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainInfoContact;
use RNIDS\Domain\Dto\DomainInfoExtension;
use RNIDS\Domain\Dto\DomainInfoNameserver;
use RNIDS\Domain\Dto\DomainInfoResponse;
use RNIDS\Domain\Dto\DomainInfoStatus;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Parses EPP domain info response XML into typed DTOs.
 */
final class DomainInfoResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): DomainInfoResponse
    {
        $xpath = XmlParser::createXPath($xml);

        return new DomainInfoResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:name'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:roid'),
            $this->parseStatuses($xpath),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:infData/domain:registrant',
            ),
            $this->parseContacts($xpath),
            $this->parseNameservers($xpath),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:clID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:crID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:upID'),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:infData/domain:crDate',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:infData/domain:upDate',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:infData/domain:exDate',
            ),
            $this->parseExtension($xpath),
        );
    }

    /**
     * @return list<DomainInfoStatus>
     */
    private function parseStatuses(\DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:status');

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

    private function parseStatusNode(\DOMNode $node): ?DomainInfoStatus
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $value = \trim($node->getAttribute('s'));

        if ('' === $value) {
            return null;
        }

        $description = \trim($node->textContent);

        return new DomainInfoStatus($value, '' === $description ? null : $description);
    }

    /**
     * @return list<DomainInfoContact>
     */
    private function parseContacts(\DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:contact');

        if (false === $nodes || 0 === $nodes->length) {
            return [];
        }

        $contacts = [];

        foreach ($nodes as $node) {
            $contact = $this->parseContactNode($node);

            if (null === $contact) {
                continue;
            }

            $contacts[] = $contact;
        }

        return $contacts;
    }

    private function parseContactNode(\DOMNode $node): ?DomainInfoContact
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $type = \trim($node->getAttribute('type'));
        $handle = \trim($node->textContent);

        if ('' === $type || '' === $handle) {
            return null;
        }

        return new DomainInfoContact($type, $handle);
    }

    /**
     * @return list<DomainInfoNameserver>
     */
    private function parseNameservers(\DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:ns/*');

        if (false === $nodes || 0 === $nodes->length) {
            return [];
        }

        $nameservers = [];

        foreach ($nodes as $node) {
            $nameserver = $this->parseNameserverNode($xpath, $node);

            if (null === $nameserver) {
                continue;
            }

            $nameservers[] = $nameserver;
        }

        return $nameservers;
    }

    private function parseNameserverNode(\DOMXPath $xpath, \DOMNode $node): ?DomainInfoNameserver
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        if ('hostObj' === $node->localName) {
            return $this->parseHostObjectNode($node);
        }

        if ('hostAttr' !== $node->localName) {
            return null;
        }

        return $this->parseHostAttributeNode($xpath, $node);
    }

    private function parseHostObjectNode(\DOMElement $node): ?DomainInfoNameserver
    {
        $name = \trim($node->textContent);

        if ('' === $name) {
            return null;
        }

        return new DomainInfoNameserver($name);
    }

    private function parseHostAttributeNode(\DOMXPath $xpath, \DOMElement $node): ?DomainInfoNameserver
    {
        $name = $this->firstRelativeNodeValue($xpath, 'domain:hostName', $node);

        if (null === $name) {
            return null;
        }

        return new DomainInfoNameserver($name, $this->relativeNodeValues($xpath, 'domain:hostAddr', $node));
    }

    private function firstRelativeNodeValue(\DOMXPath $xpath, string $query, \DOMNode $contextNode): ?string
    {
        $nodes = $xpath->query($query, $contextNode);

        if (false === $nodes || 0 === $nodes->length) {
            return null;
        }

        $value = \trim((string) $nodes->item(0)?->textContent);

        return '' === $value ? null : $value;
    }

    /**
     * @return list<string>
     */
    private function relativeNodeValues(\DOMXPath $xpath, string $query, \DOMNode $contextNode): array
    {
        $nodes = $xpath->query($query, $contextNode);

        if (false === $nodes || 0 === $nodes->length) {
            return [];
        }

        $values = [];

        foreach ($nodes as $node) {
            $value = \trim($node->textContent);

            if ('' === $value) {
                continue;
            }

            $values[] = $value;
        }

        return $values;
    }

    private function parseExtension(\DOMXPath $xpath): DomainInfoExtension
    {
        return new DomainInfoExtension(
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:isWhoisPrivacy',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:operationMode',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:notifyAdmin',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:dnsSec',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:remark',
            ),
        );
    }
}
