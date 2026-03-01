<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Represents parsed data from a successful EPP domain register response.
 */
final class DomainRegisterResponse
{
    /**
     * @param ?non-empty-string $name
     * @param \DateTimeImmutable|null $createDate
     * @param \DateTimeImmutable|null $expirationDate
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $name,
        public readonly ?\DateTimeImmutable $createDate,
        public readonly ?\DateTimeImmutable $expirationDate,
    ) {
    }
}
