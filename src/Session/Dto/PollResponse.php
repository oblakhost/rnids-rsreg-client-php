<?php

declare(strict_types=1);

namespace RNIDS\Session\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * DTO representing parsed message queue data from a poll response.
 */
final class PollResponse
{
    /**
     * @param ResponseMetadata $metadata Parsed EPP response metadata.
     * @param int|null $queueCount Number of messages currently in queue.
     * @param string|null $messageId Queue message identifier.
     * @param string|null $queueDate Queue message timestamp.
     * @param string|null $message Queue message text.
     * @param PollDomainTransferData|null $domainTransferData Typed domain transfer resData payload when present.
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?int $queueCount,
        public readonly ?string $messageId,
        public readonly ?string $queueDate,
        public readonly ?string $message,
        public readonly ?PollDomainTransferData $domainTransferData,
    ) {
    }
}
