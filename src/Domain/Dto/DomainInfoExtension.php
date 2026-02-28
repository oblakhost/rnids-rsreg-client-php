<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainInfoExtension
{
    /**
     * @param string|null $isWhoisPrivacy WHOIS privacy extension flag.
     * @param string|null $operationMode RNIDS operation mode.
     * @param string|null $notifyAdmin RNIDS notify admin flag.
     * @param string|null $dnsSec RNIDS DNSSEC extension value.
     * @param string|null $remark RNIDS extension remark.
     */
    public function __construct(
        public readonly ?string $isWhoisPrivacy,
        public readonly ?string $operationMode,
        public readonly ?string $notifyAdmin,
        public readonly ?string $dnsSec,
        public readonly ?string $remark,
    ) {
    }
}
