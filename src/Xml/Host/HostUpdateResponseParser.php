<?php

declare(strict_types=1);

namespace RNIDS\Xml\Host;

use RNIDS\Host\Dto\HostUpdateResponse;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Maps host update response metadata to a typed DTO.
 */
final class HostUpdateResponseParser
{
    /**
     * Parses an EPP host update response XML payload.
     */
    public function parse(string $xml, ResponseMetadata $metadata): HostUpdateResponse
    {
        return new HostUpdateResponse($metadata);
    }
}
