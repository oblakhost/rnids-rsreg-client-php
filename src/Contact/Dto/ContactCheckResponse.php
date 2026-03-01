<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class ContactCheckResponse
{
    /**
     * @param list<ContactCheckItem> $items
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly array $items,
    ) {
    }
}
