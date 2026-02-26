<?php

declare(strict_types=1);

namespace RNIDS\Xml\Response;

final class ResponseMetadata
{
    public function __construct(
        public readonly int $resultCode,
        public readonly string $message,
        public readonly ?string $clientTransactionId,
        public readonly ?string $serverTransactionId,
    ) {
    }

    public function knownResultCode(): ?EppResultCode
    {
        return EppResultCode::tryFrom($this->resultCode);
    }

    public function isSuccess(): bool
    {
        $knownResultCode = $this->knownResultCode();

        if (null !== $knownResultCode) {
            return match ($knownResultCode) {
                EppResultCode::CommandCompletedSuccessfully,
                EppResultCode::CommandCompletedSuccessfullyActionPending,
                EppResultCode::CommandCompletedSuccessfullyNoMessages,
                EppResultCode::CommandCompletedSuccessfullyAckToDequeue,
                EppResultCode::CommandCompletedSuccessfullyEndingSession => true,
                default => false,
            };
        }

        return $this->resultCode >= 1000 && $this->resultCode < 2000;
    }
}
