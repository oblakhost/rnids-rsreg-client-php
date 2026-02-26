<?php

declare(strict_types=1);

namespace RNIDS\Xml\Session;

use RNIDS\Session\Dto\LoginResponse;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Maps login command response metadata to a typed DTO.
 */
final class LoginResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): LoginResponse
    {
        return new LoginResponse($metadata);
    }
}
