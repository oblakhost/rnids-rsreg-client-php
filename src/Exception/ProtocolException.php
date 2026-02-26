<?php

declare(strict_types=1);

namespace RNIDS\Exception;

use RNIDS\Xml\Response\EppResultCode;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Raised when an EPP command receives a non-success result code.
 */
class ProtocolException extends \RNIDS\Exception\EppException
{
    private readonly ResponseMetadata $responseMetadata;

    /**
     * @param ResponseMetadata $responseMetadata Parsed response metadata for the failed command.
     */
    public function __construct(ResponseMetadata $responseMetadata)
    {
        $this->responseMetadata = $responseMetadata;

        parent::__construct(
            \sprintf(
                'EPP command failed with result code %d: %s',
                $responseMetadata->resultCode,
                $responseMetadata->message,
            ),
            $responseMetadata->resultCode,
        );
    }

    /**
     * Returns parsed metadata of the failed response.
     */
    public function responseMetadata(): ResponseMetadata
    {
        return $this->responseMetadata;
    }

    /**
     * Returns the raw EPP result code from the failed response.
     */
    public function resultCode(): int
    {
        return $this->responseMetadata->resultCode;
    }

    /**
     * Returns known enum representation of the result code when available.
     */
    public function knownResultCode(): ?EppResultCode
    {
        return $this->responseMetadata->knownResultCode();
    }
}
