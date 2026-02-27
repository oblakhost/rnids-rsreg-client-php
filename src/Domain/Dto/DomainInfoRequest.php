<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

final class DomainInfoRequest
{
    public const HOSTS_ALL = 'all';
    public const HOSTS_DELEGATED = 'del';
    public const HOSTS_SUBORDINATE = 'sub';
    public const HOSTS_NONE = 'none';

    public function __construct(
        public readonly string $name,
        public readonly string $hosts = self::HOSTS_ALL,
    ) {
    }
}
