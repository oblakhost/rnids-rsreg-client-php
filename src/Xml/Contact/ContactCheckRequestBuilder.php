<?php

declare(strict_types=1);

namespace RNIDS\Xml\Contact;

use RNIDS\Contact\Dto\ContactCheckRequest;
use RNIDS\Xml\NamespaceRegistry;
use RNIDS\Xml\XmlComposer;

final class ContactCheckRequestBuilder
{
    public function build(ContactCheckRequest $request, string $clTrid): string
    {
        $idsXml = \implode('', \array_map(
            static fn(string $id): string => XmlComposer::element('contact:id', $id),
            $request->ids,
        ));

        $xml = '<check>'
            . '<contact:check xmlns:contact="' . NamespaceRegistry::CONTACT . '">'
            . $idsXml
            . '</contact:check>'
            . '</check>';

        return XmlComposer::commandEnvelope($xml, $clTrid);
    }
}
