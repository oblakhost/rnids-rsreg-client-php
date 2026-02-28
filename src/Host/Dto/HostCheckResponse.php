<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class HostCheckResponse
{
    /**
     * @param list<HostCheckItem> $items
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly array $items,
    ) {
    }
}
