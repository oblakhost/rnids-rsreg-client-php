<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainRegisterContact;
use RNIDS\Domain\Dto\DomainUpdateRequest;
use RNIDS\Domain\Dto\DomainUpdateSection;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP domain update commands.
 */
final class DomainUpdateRequestBuilder
{
    private DomainExtensionXmlBuilder $extensionXmlBuilder;

    public function __construct(?DomainExtensionXmlBuilder $extensionXmlBuilder = null)
    {
        $this->extensionXmlBuilder = $extensionXmlBuilder ?? new DomainExtensionXmlBuilder();
    }

    /**
     * Builds a deterministic EPP domain update XML command.
     */
    public function build(DomainUpdateRequest $request, string $clTrid): string
    {
        $xml = '<update>'
            . '<domain:update xmlns:domain="' . NamespaceRegistry::DOMAIN . '">'
            . XmlComposer::element('domain:name', $request->name)
            . $this->sectionXml('domain:add', $request->add)
            . $this->sectionXml('domain:rem', $request->remove)
            . $this->changeXml($request)
            . '</domain:update>'
            . '</update>'
            . $this->extensionXml($request);

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }

    private function sectionXml(string $nodeName, ?DomainUpdateSection $section): string
    {
        if (null === $section) {
            return '';
        }

        return '<' . $nodeName . '>'
            . $this->contactsXml($section)
            . $this->statusesXml($section)
            . '</' . $nodeName . '>';
    }

    private function contactsXml(DomainUpdateSection $section): string
    {
        if ([] === $section->contacts) {
            return '';
        }

        return \implode(
            '',
            \array_map(
                static fn(DomainRegisterContact $contact): string => '<domain:contact type="'
                    . XmlComposer::escape($contact->type)
                    . '">'
                    . XmlComposer::escape($contact->handle)
                    . '</domain:contact>',
                $section->contacts,
            ),
        );
    }

    private function statusesXml(DomainUpdateSection $section): string
    {
        if ([] === $section->statuses) {
            return '';
        }

        return \implode(
            '',
            \array_map(
                static fn(string $status): string => '<domain:status s="'
                    . XmlComposer::escape($status)
                    . '"/>',
                $section->statuses,
            ),
        );
    }

    private function changeXml(DomainUpdateRequest $request): string
    {
        if (null === $request->registrant && null === $request->authInfo) {
            return '';
        }

        return '<domain:chg>'
            . (null !== $request->registrant ? XmlComposer::element(
                'domain:registrant',
                $request->registrant,
            ) : '')
            . (null !== $request->authInfo
                ? '<domain:authInfo>' . XmlComposer::element(
                    'domain:pw',
                    $request->authInfo,
                ) . '</domain:authInfo>'
                : '')
            . '</domain:chg>';
    }

    private function extensionXml(DomainUpdateRequest $request): string
    {
        return $this->extensionXmlBuilder->build($request->extension);
    }
}
