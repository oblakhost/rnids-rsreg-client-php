<?php

declare(strict_types=1);

namespace RNIDS\Session\Dto;

/**
 * DTO representing a session poll request.
 */
final class PollRequest
{
    /**
     * @param string $operation Poll operation, typically "req" or "ack".
     * @param string|null $messageId Queue message id used for ack operations.
     */
    public function __construct(
        public readonly string $operation = 'req',
        public readonly ?string $messageId = null,
    ) {
    }
}
