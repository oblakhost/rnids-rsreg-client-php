<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents input data for EPP domain update command.
 */
final class DomainUpdateRequest
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string|null $registrant
     * @param non-empty-string|null $authInfo
     */
    public function __construct(
        public readonly string $name,
        public readonly ?DomainUpdateSection $add = null,
        public readonly ?DomainUpdateSection $remove = null,
        public readonly ?string $registrant = null,
        public readonly ?string $authInfo = null,
        public readonly ?DomainExtension $extension = null,
    ) {
    }
}
