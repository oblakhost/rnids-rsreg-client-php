<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

/**
 * Represents input data for EPP domain transfer command.
 */
final class DomainTransferRequest
{
    public const OPERATION_REQUEST = 'request';
    public const OPERATION_QUERY = 'query';
    public const OPERATION_CANCEL = 'cancel';
    public const OPERATION_APPROVE = 'approve';
    public const OPERATION_REJECT = 'reject';

    /**
     * @param non-empty-string $operation
     * @param non-empty-string $name
     * @param non-empty-string $periodUnit
     * @param ?non-empty-string $authInfo
     */
    public function __construct(
        public readonly string $operation,
        public readonly string $name,
        public readonly ?int $period,
        public readonly string $periodUnit = 'y',
        public readonly ?string $authInfo = null,
    ) {
    }
}
