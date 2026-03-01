<?php

declare(strict_types=1);

namespace RNIDS\Xml\Contact;

use RNIDS\Contact\Dto\ContactInfoRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

final class ContactInfoRequestBuilder
{
    public function build(ContactInfoRequest $request, string $clTrid): string
    {
        $xml = '<info>'
            . '<contact:info xmlns:contact="' . NamespaceRegistry::CONTACT . '">'
            . XmlComposer::element('contact:id', $request->id)
            . '</contact:info>'
            . '</info>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }
}
