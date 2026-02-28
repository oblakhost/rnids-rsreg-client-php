<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostCheckRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP host check commands.
 */
final class HostCheckRequestBuilder
{
    public function build(HostCheckRequest $request, string $clTrid): string
    {
        $namesXml = \implode(
            '',
            \array_map(
                static fn(string $name): string => XmlComposer::element('host:name', $name),
                $request->names,
            ),
        );

        $xml = '<check>'
            . '<host:check xmlns:host="' . NamespaceRegistry::HOST . '">'
            . $namesXml
            . '</host:check>'
            . '</check>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }
}
