<?php

declare(strict_types=1);

namespace RNIDS\Exception;

use RNIDS\Xml\Response\EppResultCode;
use RNIDS\Xml\Response\ResponseMetadata;

class ProtocolException extends \RNIDS\Exception\EppException
{
    private readonly ResponseMetadata $responseMetadata;

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

    public function responseMetadata(): ResponseMetadata
    {
        return $this->responseMetadata;
    }

    public function resultCode(): int
    {
        return $this->responseMetadata->resultCode;
    }

    public function knownResultCode(): ?EppResultCode
    {
        return $this->responseMetadata->knownResultCode();
    }
}
