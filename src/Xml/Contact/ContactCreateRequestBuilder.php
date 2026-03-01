<?php

declare(strict_types=1);

namespace RNIDS\Xml\Contact;

use RNIDS\Contact\Dto\ContactAddress;
use RNIDS\Contact\Dto\ContactCreateRequest;
use RNIDS\Contact\Dto\ContactExtension;
use RNIDS\Contact\Dto\ContactPostalInfo;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

final class ContactCreateRequestBuilder
{
    public function build(ContactCreateRequest $request, string $clTrid): string
    {
        $xml = '<create>'
            . '<contact:create xmlns:contact="' . NamespaceRegistry::CONTACT . '">'
            . XmlComposer::element('contact:id', $request->id)
            . $this->postalInfoXml($request->postalInfo)
            . $this->optionalElement('contact:voice', $request->voice)
            . $this->optionalElement('contact:fax', $request->fax)
            . XmlComposer::element('contact:email', $request->email)
            . $this->authInfoXml($request->authInfo)
            . $this->discloseXml($request->disclose)
            . '</contact:create>'
            . '</create>'
            . $this->extensionXml($request->extension);

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }

    private function postalInfoXml(ContactPostalInfo $postalInfo): string
    {
        return '<contact:postalInfo type="' . XmlComposer::escape($postalInfo->type) . '">'
            . XmlComposer::element('contact:name', $postalInfo->name)
            . $this->optionalElement('contact:org', $postalInfo->organization)
            . $this->addressXml($postalInfo->address)
            . '</contact:postalInfo>';
    }

    private function addressXml(ContactAddress $address): string
    {
        $streetsXml = \implode('', \array_map(
            static fn(string $street): string => XmlComposer::element('contact:street', $street),
            $address->streets,
        ));

        return '<contact:addr>'
            . $streetsXml
            . XmlComposer::element('contact:city', $address->city)
            . $this->optionalElement('contact:sp', $address->province)
            . $this->optionalElement('contact:pc', $address->postalCode)
            . XmlComposer::element('contact:cc', $address->countryCode)
            . '</contact:addr>';
    }

    private function authInfoXml(?string $authInfo): string
    {
        if (null === $authInfo) {
            return '';
        }

        return '<contact:authInfo>' . XmlComposer::element('contact:pw', $authInfo) . '</contact:authInfo>';
    }

    private function discloseXml(?int $disclose): string
    {
        if (null === $disclose) {
            return '';
        }

        return '<contact:disclose flag="' . (string) $disclose . '">'
            . '<contact:name/><contact:org/><contact:addr/><contact:voice/><contact:email/>'
            . '</contact:disclose>';
    }

    private function extensionXml(?ContactExtension $extension): string
    {
        if (null === $extension) {
            return '';
        }

        $parts = \array_values(\array_filter([
            $this->optionalElement('contactExt:ident', $extension->ident),
            $this->optionalElement('contactExt:identDescription', $extension->identDescription),
            $this->optionalElement('contactExt:identExpiry', $extension->identExpiry),
            $this->optionalElement('contactExt:isLegalEntity', $extension->isLegalEntity),
            $this->optionalElement('contactExt:identKind', $extension->identKind),
            $this->optionalElement('contactExt:vatNo', $extension->vatNo),
        ]));

        if ([] === $parts) {
            return '';
        }

        return '<extension><contactExt:contact-ext xmlns:contactExt="' . NamespaceRegistry::RNIDS_CONTACT_EXT . '">'
            . \implode('', $parts)
            . '</contactExt:contact-ext></extension>';
    }

    private function optionalElement(string $name, ?string $value): string
    {
        if (null === $value) {
            return '';
        }

        return XmlComposer::element($name, $value);
    }
}
