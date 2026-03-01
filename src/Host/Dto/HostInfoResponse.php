<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class HostInfoResponse
{
    /**
     * @param list<string> $statuses
     * @param list<string> $ipv4
     * @param list<string> $ipv6
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $name,
        public readonly ?string $roid,
        public readonly array $statuses,
        public readonly array $ipv4,
        public readonly array $ipv6,
        public readonly ?string $clientId,
        public readonly ?string $createClientId,
        public readonly ?string $updateClientId,
        public readonly ?\DateTimeImmutable $createDate,
        public readonly ?\DateTimeImmutable $updateDate,
        public readonly ?\DateTimeImmutable $transferDate,
    ) {
    }
}
