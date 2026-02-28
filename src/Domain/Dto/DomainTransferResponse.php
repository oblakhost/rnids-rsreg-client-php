<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Represents parsed data from a successful EPP domain transfer response.
 */
final class DomainTransferResponse
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
        public readonly ResponseMetadata $metadata,
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
