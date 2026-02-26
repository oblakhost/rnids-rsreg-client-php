<?php

declare(strict_types=1);

namespace RNIDS\Xml\Parser;

use RNIDS\Xml\NamespaceRegistry;

/**
 * Provides namespace-safe XPath helpers for EPP response parsing.
 */
final class XmlParser
{
    /**
     * Creates a DOMXPath instance with all known EPP/RNIDS namespaces registered.
     *
     * @throws \RNIDS\Exception\MalformedResponseException
     */
    public static function createXPath(string $xml): \DOMXPath
    {
        $document = new \DOMDocument();

        if (true !== @$document->loadXML($xml)) {
            throw new \RNIDS\Exception\MalformedResponseException('EPP response contains malformed XML.');
        }

        $xpath = new \DOMXPath($document);
        NamespaceRegistry::registerXpathNamespaces($xpath);

        return $xpath;
    }

    /**
     * Returns the first node text content for an XPath query or null when absent.
     */
    public static function firstNodeValue(\DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);

        if (false === $nodes || 0 === $nodes->length) {
            return null;
        }

        $value = $nodes->item(0)?->textContent;

        if (null === $value) {
            return null;
        }

        return \trim($value);
    }

    /**
     * Returns all non-empty text values matching an XPath query.
     *
     * @return list<string>
     */
    public static function nodeValues(\DOMXPath $xpath, string $query): array
    {
        $nodes = $xpath->query($query);

        if (false === $nodes || 0 === $nodes->length) {
            return [];
        }

        $result = [];

        foreach ($nodes as $node) {
            $value = \trim($node->textContent);

            if ('' === $value) {
                continue;
            }

            $result[] = $value;
        }

        return $result;
    }

    /**
     * Returns the first node numeric value as integer or null when absent/non-numeric.
     */
    public static function firstNodeInt(\DOMXPath $xpath, string $query): ?int
    {
        $value = self::firstNodeValue($xpath, $query);

        if (null === $value || !\is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
