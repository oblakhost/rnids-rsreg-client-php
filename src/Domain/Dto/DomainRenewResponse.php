<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Represents parsed data from a successful EPP domain renew response.
 */
final class DomainRenewResponse
{
    /**
     * @param ?non-empty-string $name
     * @param \DateTimeImmutable|null $expirationDate
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $name,
        public readonly ?\DateTimeImmutable $expirationDate,
    ) {
    }
}
