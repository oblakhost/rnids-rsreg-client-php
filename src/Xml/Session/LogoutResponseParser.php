<?php

declare(strict_types=1);

namespace RNIDS\Xml\Session;

use RNIDS\Session\Dto\LogoutResponse;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Maps logout command response metadata to a typed DTO.
 */
final class LogoutResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): LogoutResponse
    {
        return new LogoutResponse($metadata);
    }
}
