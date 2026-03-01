<?php

declare(strict_types=1);

namespace RNIDS\Xml\Contact;

use RNIDS\Contact\Dto\ContactUpdateResponse;
use RNIDS\Xml\Response\ResponseMetadata;

final class ContactUpdateResponseParser
{
    public function parse(string $xml, ResponseMetadata $metadata): ContactUpdateResponse
    {
        return new ContactUpdateResponse($metadata);
    }
}
