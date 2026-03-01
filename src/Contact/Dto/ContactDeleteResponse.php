<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class ContactDeleteResponse
{
    public function __construct(public readonly ResponseMetadata $metadata)
    {
    }
}
