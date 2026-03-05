<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainInfoNameserver;
use RNIDS\Domain\Dto\DomainInfoResponse;
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
        $contacts = $this->parseContactsByType($xpath);

        return new DomainInfoResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:name'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:roid'),
            $this->parseStatuses($xpath),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:infData/domain:registrant',
            ),
            $contacts['admin'],
            $contacts['tech'],
            $this->parseNameservers($xpath),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:clID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:crID'),
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/domain:infData/domain:upID'),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:infData/domain:crDate',
            ),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:infData/domain:upDate',
            ),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:resData/domain:infData/domain:exDate',
            ),
            $this->parseBooleanNode(
                XmlParser::firstNodeValue(
                    $xpath,
                    '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:isWhoisPrivacy',
                ),
            ),
            $this->parseBooleanNode(
                XmlParser::firstNodeValue(
                    $xpath,
                    '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:isDomainVerified',
                ),
            ),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:domainVerifiedOn',
            ),
            XmlParser::firstNodeDateTime(
                $xpath,
                '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:domainVerificationRequestExpiresOn',
            ),
            $this->parseBooleanNode(
                XmlParser::firstNodeValue(
                    $xpath,
                    '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:isWhoisPrivacyPaid',
                ),
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:operationMode',
            ),
            $this->parseBooleanNode(
                XmlParser::firstNodeValue(
                    $xpath,
                    '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:notifyAdmin',
                ),
            ),
            $this->parseBooleanNode(
                XmlParser::firstNodeValue(
                    $xpath,
                    '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:dnsSec',
                ),
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/domainExt:domain-ext/domainExt:remark',
            ),
        );
    }

    /**
     * @return list<string>
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
     * @return array{admin: string|null, tech: string|null}
     */
    private function parseContactsByType(\DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:contact');

        if (false === $nodes || 0 === $nodes->length) {
            return [
                'admin' => null,
                'tech' => null,
            ];
        }

        $contacts = [
            'admin' => null,
            'tech' => null,
        ];

        foreach ($nodes as $node) {
            $contact = $this->parseContactNode($node);

            if (null === $contact) {
                continue;
            }

            if ('admin' !== $contact['type'] && 'tech' !== $contact['type']) {
                continue;
            }

            $contacts[$contact['type']] = $contact['handle'];
        }

        return $contacts;
    }

    /**
     * @return array{type: string, handle: string}|null
     */
    private function parseContactNode(\DOMNode $node): ?array
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $type = \trim($node->getAttribute('type'));
        $handle = \trim($node->textContent);

        if ('' === $type || '' === $handle) {
            return null;
        }

        return [
            'handle' => $handle,
            'type' => $type,
        ];
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

    private function parseBooleanNode(?string $value): bool
    {
        if (null === $value) {
            return false;
        }

        return \in_array(\strtolower(\trim($value)), [ '1', 'true', 'yes' ], true);
    }
}
