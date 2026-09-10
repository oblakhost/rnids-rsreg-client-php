<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainExtension;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds RNIDS domain extension XML payload for domain create/update commands.
 */
final class DomainExtensionXmlBuilder
{
    public function build(?DomainExtension $extension, string $additionalXml = ''): string
    {
        if (null === $extension) {
            return $this->envelope($additionalXml);
        }

        $parts = \array_values(\array_filter([
            $this->remarkXml($extension->remark),
            $this->boolXml('domainExt:isWhoisPrivacy', $extension->isWhoisPrivacy),
            $this->operationModeXml($extension->operationMode),
            $this->boolXml('domainExt:notifyAdmin', $extension->notifyAdmin),
            $this->boolXml('domainExt:dnsSec', $extension->dnsSec),
        ]));

        if ([] === $parts) {
            return $this->envelope($additionalXml);
        }

        return $this->envelope(
            '<domainExt:domain-ext xmlns:domainExt="' . NamespaceRegistry::RNIDS_DOMAIN_EXT . '">'
                . \implode('', $parts)
                . '</domainExt:domain-ext>'
                . $additionalXml,
        );
    }

    private function envelope(string $xml): string
    {
        return '' === $xml ? '' : '<extension>' . $xml . '</extension>';
    }

    private function remarkXml(?string $remark): ?string
    {
        return null !== $remark ? XmlComposer::element('domainExt:remark', $remark) : null;
    }

    private function operationModeXml(?string $operationMode): ?string
    {
        return null !== $operationMode
            ? XmlComposer::element('domainExt:operationMode', $operationMode)
            : null;
    }

    private function boolXml(string $nodeName, ?bool $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return XmlComposer::element($nodeName, $value ? 'true' : 'false');
    }
}
