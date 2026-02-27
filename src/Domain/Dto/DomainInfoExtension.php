<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainInfoExtension
{
    public function __construct(
        public readonly ?string $isWhoisPrivacy,
        public readonly ?string $operationMode,
        public readonly ?string $notifyAdmin,
        public readonly ?string $dnsSec,
        public readonly ?string $remark,
    ) {
    }
}
