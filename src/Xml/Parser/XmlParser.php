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
        $previousErrorMode = \libxml_use_internal_errors(true);
        \libxml_clear_errors();

        try {
            if (true !== $document->loadXML($xml)) {
                throw new \RNIDS\Exception\MalformedResponseException(
                    self::buildMalformedXmlMessage(\libxml_get_errors()),
                );
            }
        } finally {
            \libxml_clear_errors();
            \libxml_use_internal_errors($previousErrorMode);
        }

        $xpath = new \DOMXPath($document);
        NamespaceRegistry::registerXpathNamespaces($xpath);

        return $xpath;
    }

    /**
     * @param list<\LibXMLError> $errors
     */
    private static function buildMalformedXmlMessage(array $errors): string
    {
        $baseMessage = 'EPP response contains malformed XML';

        if ([] === $errors) {
            return $baseMessage . '.';
        }

        $firstError = $errors[0];
        $normalizedMessage = self::normalizeLibXmlMessage($firstError->message);

        if ('' === $normalizedMessage) {
            return \sprintf('%s (line %d, column %d).', $baseMessage, $firstError->line, $firstError->column);
        }

        return \sprintf(
            '%s: %s (line %d, column %d).',
            $baseMessage,
            $normalizedMessage,
            $firstError->line,
            $firstError->column,
        );
    }

    private static function normalizeLibXmlMessage(string $message): string
    {
        return \trim((string) \preg_replace('/\s+/', ' ', \trim($message)));
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

    /**
     * Returns the first node value parsed as DateTimeImmutable or null when absent/invalid.
     */
    public static function firstNodeDateTime(\DOMXPath $xpath, string $query): ?\DateTimeImmutable
    {
        $value = self::firstNodeValue($xpath, $query);

        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }
    }
}
