<?php

declare(strict_types=1);

namespace RNIDS\Session\Dto;

/**
 * DTO representing a session poll request.
 */
final class PollRequest
{
    public function __construct(
        public readonly string $operation = 'req',
        public readonly ?string $messageId = null,
    ) {
    }
}
