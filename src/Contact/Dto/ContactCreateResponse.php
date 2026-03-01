<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class ContactCreateResponse
{
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $id,
        public readonly ?string $createDate,
    ) {
    }
}
