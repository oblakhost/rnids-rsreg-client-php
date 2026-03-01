<?php

declare(strict_types=1);

namespace RNIDS\Xml\Contact;

use RNIDS\Contact\Dto\ContactDeleteResponse;
use RNIDS\Xml\Response\ResponseMetadata;

final class ContactDeleteResponseParser
{
    public function parse(string $xml, ResponseMetadata $metadata): ContactDeleteResponse
    {
        return new ContactDeleteResponse($metadata);
    }
}
