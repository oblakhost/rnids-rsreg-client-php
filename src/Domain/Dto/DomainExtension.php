<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents RNIDS domain extension fields shared by register/update commands.
 */
final class DomainExtension
{
    /**
     * @param ?non-empty-string $remark
     * @param ?non-empty-string $operationMode
     */
    public function __construct(
        public readonly ?string $remark,
        public readonly ?bool $isWhoisPrivacy,
        public readonly ?string $operationMode,
        public readonly ?bool $notifyAdmin,
        public readonly ?bool $dnsSec,
    ) {
    }
}
