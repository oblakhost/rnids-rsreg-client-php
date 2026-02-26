<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class DomainCheckResponse
{
    /**
     * @param list<DomainCheckItem> $items
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly array $items,
    ) {
    }
}
