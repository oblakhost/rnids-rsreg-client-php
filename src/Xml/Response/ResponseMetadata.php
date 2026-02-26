<?php

declare(strict_types=1);

namespace RNIDS\Xml\Response;

/**
 * Parsed high-level metadata from an EPP response frame.
 */
final class ResponseMetadata
{
    /**
     * @param int $resultCode Numeric EPP result code.
     * @param string $message Human-readable result message.
     * @param string|null $clientTransactionId Client transaction id (clTRID).
     * @param string|null $serverTransactionId Server transaction id (svTRID).
     */
    public function __construct(
        public readonly int $resultCode,
        public readonly string $message,
        public readonly ?string $clientTransactionId,
        public readonly ?string $serverTransactionId,
    ) {
    }

    /**
     * Returns enum representation of the result code when known.
     */
    public function knownResultCode(): ?EppResultCode
    {
        return EppResultCode::tryFrom($this->resultCode);
    }

    /**
     * Indicates whether the result code represents successful command execution.
     */
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
