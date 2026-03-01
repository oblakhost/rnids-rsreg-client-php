<?php

declare(strict_types=1);

namespace RNIDS\Xml\Contact;

use RNIDS\Contact\Dto\ContactAddress;
use RNIDS\Contact\Dto\ContactExtension;
use RNIDS\Contact\Dto\ContactInfoResponse;
use RNIDS\Contact\Dto\ContactPostalInfo;
use RNIDS\Contact\Dto\ContactStatus;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

final class ContactInfoResponseParser
{
    public function parse(string $xml, ResponseMetadata $metadata): ContactInfoResponse
    {
        $xpath = XmlParser::createXPath($xml);

        return new ContactInfoResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/contact:infData/contact:id'),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:roid',
            ),
            $this->parseStatuses($xpath),
            $this->parsePostalInfo($xpath),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:voice',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:fax',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:email',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:clID',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:crID',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:upID',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:crDate',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:upDate',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:trDate',
            ),
            XmlParser::firstNodeInt(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:infData/contact:disclose/@flag',
            ),
            $this->parseExtension($xpath),
        );
    }

    /**
     * @return list<ContactStatus>
     */
    private function parseStatuses(\DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/epp:epp/epp:response/epp:resData/contact:infData/contact:status');

        if (false === $nodes || 0 === $nodes->length) {
            return [];
        }

        $items = [];

        foreach ($nodes as $node) {
            $status = $this->parseStatusNode($node);

            if (null === $status) {
                continue;
            }

            $items[] = $status;
        }

        return $items;
    }

    private function parseStatusNode(\DOMNode $node): ?ContactStatus
    {
        if (!$node instanceof \DOMElement) {
            return null;
        }

        $value = \trim($node->getAttribute('s'));

        if ('' === $value) {
            return null;
        }

        $description = \trim($node->textContent);

        return new ContactStatus($value, '' === $description ? null : $description);
    }

    private function parsePostalInfo(\DOMXPath $xpath): ?ContactPostalInfo
    {
        $postalInfoNodes = $xpath->query(
            '/epp:epp/epp:response/epp:resData/contact:infData/contact:postalInfo',
        );

        if (false === $postalInfoNodes) {
            return null;
        }

        $postalInfoNode = $postalInfoNodes->item(0);

        if (!$postalInfoNode instanceof \DOMElement) {
            return null;
        }

        $type = $this->normalizePostalInfoType($postalInfoNode);

        $name = $this->firstRelativeNodeValue($xpath, 'contact:name', $postalInfoNode);
        $city = $this->firstRelativeNodeValue($xpath, 'contact:addr/contact:city', $postalInfoNode);
        $countryCode = $this->firstRelativeNodeValue($xpath, 'contact:addr/contact:cc', $postalInfoNode);

        if (null === $name || null === $city || null === $countryCode) {
            return null;
        }

        $streets = $this->relativeNodeValues($xpath, 'contact:addr/contact:street', $postalInfoNode);

        if ([] === $streets) {
            return null;
        }

        return new ContactPostalInfo(
            $type,
            $name,
            $this->firstRelativeNodeValue($xpath, 'contact:org', $postalInfoNode),
            new ContactAddress(
                $streets,
                $city,
                $countryCode,
                $this->firstRelativeNodeValue($xpath, 'contact:addr/contact:sp', $postalInfoNode),
                $this->firstRelativeNodeValue($xpath, 'contact:addr/contact:pc', $postalInfoNode),
            ),
        );
    }

    private function normalizePostalInfoType(\DOMElement $postalInfoNode): string
    {
        $type = \trim($postalInfoNode->getAttribute('type'));

        return '' === $type ? ContactPostalInfo::TYPE_LOC : $type;
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

    private function parseExtension(\DOMXPath $xpath): ContactExtension
    {
        return new ContactExtension(
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/contactExt:contact-ext/contactExt:ident',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/contactExt:contact-ext/contactExt:identDescription',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/contactExt:contact-ext/contactExt:identExpiry',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/contactExt:contact-ext/contactExt:identKind',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/contactExt:contact-ext/contactExt:isLegalEntity',
            ),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:extension/contactExt:contact-ext/contactExt:vatNo',
            ),
        );
    }
}
