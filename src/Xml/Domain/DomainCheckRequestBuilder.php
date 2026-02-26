<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainCheckRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP domain check commands.
 */
final class DomainCheckRequestBuilder
{
    /**
     * Builds a deterministic EPP domain check XML command.
     */
    public function build(DomainCheckRequest $request, string $clTrid): string
    {
        $namesXml = \implode(
            '',
            \array_map(
                static fn(string $name): string => XmlComposer::element('domain:name', $name),
                $request->names,
            ),
        );

        $xml = '<check>'
            . '<domain:check xmlns:domain="' . NamespaceRegistry::DOMAIN . '">'
            . $namesXml
            . '</domain:check>'
            . '</check>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }
}
