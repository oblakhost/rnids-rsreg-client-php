<?php

declare(strict_types=1);

namespace RNIDS\Session\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * DTO representing the result of a session logout command.
 */
final class LogoutResponse
{
    /**
     * @param ResponseMetadata $metadata Parsed metadata from logout response.
     */
    public function __construct(public readonly ResponseMetadata $metadata)
    {
    }
}
