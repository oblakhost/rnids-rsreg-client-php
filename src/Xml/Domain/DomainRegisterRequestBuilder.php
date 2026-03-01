<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainRegisterContact;
use RNIDS\Domain\Dto\DomainRegisterNameserver;
use RNIDS\Domain\Dto\DomainRegisterRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP domain register (create) commands.
 */
final class DomainRegisterRequestBuilder
{
    private DomainExtensionXmlBuilder $extensionXmlBuilder;

    public function __construct(?DomainExtensionXmlBuilder $extensionXmlBuilder = null)
    {
        $this->extensionXmlBuilder = $extensionXmlBuilder ?? new DomainExtensionXmlBuilder();
    }

    /**
     * Builds a deterministic EPP domain register XML command.
     */
    public function build(DomainRegisterRequest $request, string $clTrid): string
    {
        $xml = '<create>'
            . '<domain:create xmlns:domain="' . NamespaceRegistry::DOMAIN . '">'
            . XmlComposer::element('domain:name', $request->name)
            . $this->periodXml($request)
            . $this->nameserversXml($request)
            . XmlComposer::element('domain:registrant', $request->registrant)
            . $this->contactsXml($request)
            . $this->authInfoXml($request)
            . '</domain:create>'
            . '</create>'
            . $this->extensionXml($request);

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }

    private function periodXml(DomainRegisterRequest $request): string
    {
        if (null === $request->period) {
            return '';
        }

        return '<domain:period unit="' . XmlComposer::escape($request->periodUnit) . '">'
            . (string) $request->period
            . '</domain:period>';
    }

    private function nameserversXml(DomainRegisterRequest $request): string
    {
        if ([] === $request->nameservers) {
            return '';
        }

        return '<domain:ns>'
            . \implode(
                '',
                \array_map(
                    fn(DomainRegisterNameserver $nameserver): string => $this->nameserverXml($nameserver),
                    $request->nameservers,
                ),
            )
            . '</domain:ns>';
    }

    private function nameserverXml(DomainRegisterNameserver $nameserver): string
    {
        if ([] === $nameserver->addresses) {
            return XmlComposer::element('domain:hostObj', $nameserver->name);
        }

        return '<domain:hostAttr>'
            . XmlComposer::element('domain:hostName', $nameserver->name)
            . \implode(
                '',
                \array_map(
                    static fn(string $address): string => XmlComposer::element('domain:hostAddr', $address),
                    $nameserver->addresses,
                ),
            )
            . '</domain:hostAttr>';
    }

    private function contactsXml(DomainRegisterRequest $request): string
    {
        return \implode(
            '',
            \array_map(
                static fn(DomainRegisterContact $contact): string => '<domain:contact type="'
                    . XmlComposer::escape($contact->type)
                    . '">'
                    . XmlComposer::escape($contact->handle)
                    . '</domain:contact>',
                $request->contacts,
            ),
        );
    }

    private function authInfoXml(DomainRegisterRequest $request): string
    {
        if (null === $request->authInfo) {
            return '';
        }

        return '<domain:authInfo>'
            . XmlComposer::element('domain:pw', $request->authInfo)
            . '</domain:authInfo>';
    }

    private function extensionXml(DomainRegisterRequest $request): string
    {
        return $this->extensionXmlBuilder->build($request->extension);
    }
}
