<?php

declare(strict_types=1);

namespace RNIDS\Xml;

use RNIDS\Exception\ProtocolExceptionFactory;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Enforces successful EPP result codes for command execution.
 */
final class ResultCodePolicy
{
    /**
     * Throws a protocol exception when metadata does not represent success.
     */
    public static function assertSuccess(ResponseMetadata $metadata): void
    {
        if ($metadata->isSuccess()) {
            return;
        }

        throw ProtocolExceptionFactory::fromMetadata($metadata);
    }
}
