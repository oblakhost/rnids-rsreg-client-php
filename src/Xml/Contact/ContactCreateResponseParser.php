<?php

declare(strict_types=1);

namespace RNIDS\Xml\Contact;

use RNIDS\Contact\Dto\ContactCreateResponse;
use RNIDS\Xml\Parser\XmlParser;
use RNIDS\Xml\Response\ResponseMetadata;

final class ContactCreateResponseParser
{
    public function parse(string $xml, ResponseMetadata $metadata): ContactCreateResponse
    {
        $xpath = XmlParser::createXPath($xml);

        return new ContactCreateResponse(
            $metadata,
            XmlParser::firstNodeValue($xpath, '/epp:epp/epp:response/epp:resData/contact:creData/contact:id'),
            XmlParser::firstNodeValue(
                $xpath,
                '/epp:epp/epp:response/epp:resData/contact:creData/contact:crDate',
            ),
        );
    }
}
