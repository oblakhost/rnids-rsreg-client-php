<?php

declare(strict_types=1);

namespace RNIDS\Session\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * DTO representing parsed message queue data from a poll response.
 */
final class PollResponse
{
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?int $queueCount,
        public readonly ?string $messageId,
        public readonly ?string $queueDate,
        public readonly ?string $message,
    ) {
    }
}
