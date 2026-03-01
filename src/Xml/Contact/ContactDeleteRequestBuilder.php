<?php

declare(strict_types=1);

namespace RNIDS\Xml\Contact;

use RNIDS\Contact\Dto\ContactDeleteRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

final class ContactDeleteRequestBuilder
{
    public function build(ContactDeleteRequest $request, string $clTrid): string
    {
        $xml = '<delete>'
            . '<contact:delete xmlns:contact="' . NamespaceRegistry::CONTACT . '">'
            . XmlComposer::element('contact:id', $request->id)
            . '</contact:delete>'
            . '</delete>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }
}
