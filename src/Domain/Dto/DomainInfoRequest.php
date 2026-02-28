<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainInfoRequest
{
    public const HOSTS_ALL = 'all';
    public const HOSTS_DELEGATED = 'del';
    public const HOSTS_SUBORDINATE = 'sub';
    public const HOSTS_NONE = 'none';

    /**
     * @param string $name Domain name.
     * @param string $hosts Host reporting mode.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $hosts = self::HOSTS_ALL,
    ) {
    }
}
