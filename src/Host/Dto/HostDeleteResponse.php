<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class HostDeleteResponse
{
    public function __construct(public readonly ResponseMetadata $metadata)
    {
    }
}
