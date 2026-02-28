<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostAddress;
use RNIDS\Host\Dto\HostCreateRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP host create commands.
 */
final class HostCreateRequestBuilder
{
    /**
     * Builds an EPP host create command XML payload.
     */
    public function build(HostCreateRequest $request, string $clTrid): string
    {
        $xml = '<create>'
            . '<host:create xmlns:host="' . NamespaceRegistry::HOST . '">'
            . XmlComposer::element('host:name', $request->name)
            . $this->addressesXml($request)
            . '</host:create>'
            . '</create>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }

    private function addressesXml(HostCreateRequest $request): string
    {
        if ([] === $request->addresses) {
            return '';
        }

        return \implode('', \array_map(
            static fn(HostAddress $address): string => '<host:addr ip="'
                . XmlComposer::escape($address->ipVersion)
                . '">'
                . XmlComposer::escape($address->address)
                . '</host:addr>',
            $request->addresses,
        ));
    }
}
