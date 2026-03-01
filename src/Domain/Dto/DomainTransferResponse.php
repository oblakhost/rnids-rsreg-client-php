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
     * @param \DateTimeImmutable|null $requestDate
     * @param ?non-empty-string $actionClientId
     * @param \DateTimeImmutable|null $actionDate
     * @param \DateTimeImmutable|null $expirationDate
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $name,
        public readonly ?string $transferStatus,
        public readonly ?string $requestClientId,
        public readonly ?\DateTimeImmutable $requestDate,
        public readonly ?string $actionClientId,
        public readonly ?\DateTimeImmutable $actionDate,
        public readonly ?\DateTimeImmutable $expirationDate,
    ) {
    }
}
