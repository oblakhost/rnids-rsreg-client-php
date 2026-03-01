<?php

declare(strict_types=1);

namespace RNIDS\Session\Dto;

/**
 * Typed poll resData payload for domain transfer queue notifications.
 */
final class PollDomainTransferData
{
    /**
     * @param ?non-empty-string $name
     * @param ?non-empty-string $transferStatus
     * @param ?non-empty-string $requestClientId
     * @param ?non-empty-string $requestDate
     * @param ?non-empty-string $actionClientId
     * @param ?non-empty-string $actionDate
     * @param ?non-empty-string $expirationDate
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $transferStatus,
        public readonly ?string $requestClientId,
        public readonly ?string $requestDate,
        public readonly ?string $actionClientId,
        public readonly ?string $actionDate,
        public readonly ?string $expirationDate,
    ) {
    }
}
