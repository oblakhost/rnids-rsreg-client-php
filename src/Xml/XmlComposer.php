<?php

declare(strict_types=1);

namespace RNIDS\Xml;

/**
 * Provides small deterministic helpers for composing EPP XML fragments.
 */
final class XmlComposer
{
    /**
     * Escapes plain text for safe use in XML element and attribute content.
     */
    public static function escape(string $value): string
    {
        return \htmlspecialchars($value, \ENT_XML1 | \ENT_COMPAT, 'UTF-8');
    }

    /**
     * Builds a full EPP command envelope with a mandatory clTRID.
     */
    public static function commandEnvelope(string $commandInnerXml, string $clTrid): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<epp xmlns="' . NamespaceRegistry::EPP . '">'
            . '<command>'
            . $commandInnerXml
            . '<clTRID>' . self::escape($clTrid) . '</clTRID>'
            . '</command>'
            . '</epp>';
    }

    /**
     * Builds a simple XML element with escaped text content.
     */
    public static function element(string $name, string $value): string
    {
        return '<' . $name . '>' . self::escape($value) . '</' . $name . '>';
    }
}
