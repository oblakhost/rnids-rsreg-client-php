<?php

declare(strict_types=1);

namespace RNIDS\Host\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

final class HostInfoResponse
{
    /**
     * @param list<HostStatus> $statuses
     * @param list<HostAddress> $addresses
     */
    public function __construct(
        public readonly ResponseMetadata $metadata,
        public readonly ?string $name,
        public readonly ?string $roid,
        public readonly array $statuses,
        public readonly array $addresses,
        public readonly ?string $clientId,
        public readonly ?string $createClientId,
        public readonly ?string $updateClientId,
        public readonly ?string $createDate,
        public readonly ?string $updateDate,
        public readonly ?string $transferDate,
    ) {
    }
}
