<?php

declare(strict_types=1);

namespace RNIDS\Session\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * DTO representing the result of a session login command.
 */
final class LoginResponse
{
    /**
     * @param ResponseMetadata $metadata Parsed metadata from login response.
     */
    public function __construct(public readonly ResponseMetadata $metadata)
    {
    }
}
