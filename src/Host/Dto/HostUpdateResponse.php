<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class HostUpdateResponse
{
    /**
     * Creates a host update response DTO.
     */
    public function __construct(public readonly ResponseMetadata $metadata)
    {
    }
}
