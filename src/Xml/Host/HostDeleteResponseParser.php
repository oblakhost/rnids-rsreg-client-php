<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostDeleteResponse;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Maps host delete response metadata to a typed DTO.
 */
final class HostDeleteResponseParser
{
    public function parse(string $xml, ResponseMetadata $metadata): HostDeleteResponse
    {
        return new HostDeleteResponse($metadata);
    }
}
