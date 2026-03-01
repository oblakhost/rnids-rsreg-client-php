<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class HostCreateResponse
{
    /**
     * Creates a host create response DTO.
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $name,
        public readonly ?\DateTimeImmutable $createDate,
    ) {
    }
}
