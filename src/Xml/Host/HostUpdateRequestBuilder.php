<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostAddress;
use RNIDS\Host\Dto\HostUpdateRequest;
use RNIDS\Host\Dto\HostUpdateSection;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP host update commands.
 */
final class HostUpdateRequestBuilder
{
    public function build(HostUpdateRequest $request, string $clTrid): string
    {
        $xml = '<update>'
            . '<host:update xmlns:host="' . NamespaceRegistry::HOST . '">'
            . XmlComposer::element('host:name', $request->name)
            . $this->sectionXml('host:add', $request->add)
            . $this->sectionXml('host:rem', $request->remove)
            . $this->changeXml($request)
            . '</host:update>'
            . '</update>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }

    private function sectionXml(string $elementName, ?HostUpdateSection $section): string
    {
        if (null === $section) {
            return '';
        }

        return '<' . $elementName . '>'
            . $this->sectionAddressesXml($section)
            . $this->sectionStatusesXml($section)
            . '</' . $elementName . '>';
    }

    private function sectionAddressesXml(HostUpdateSection $section): string
    {
        if ([] === $section->addresses) {
            return '';
        }

        return \implode('', \array_map(
            static fn(HostAddress $address): string => '<host:addr ip="'
                . XmlComposer::escape($address->ipVersion)
                . '">'
                . XmlComposer::escape($address->address)
                . '</host:addr>',
            $section->addresses,
        ));
    }

    private function sectionStatusesXml(HostUpdateSection $section): string
    {
        if ([] === $section->statuses) {
            return '';
        }

        return \implode('', \array_map(
            static fn(string $status): string => '<host:status s="'
                . XmlComposer::escape($status)
                . '"/>',
            $section->statuses,
        ));
    }

    private function changeXml(HostUpdateRequest $request): string
    {
        if (null === $request->newName) {
            return '';
        }

        return '<host:chg>'
            . XmlComposer::element('host:name', $request->newName)
            . '</host:chg>';
    }
}
