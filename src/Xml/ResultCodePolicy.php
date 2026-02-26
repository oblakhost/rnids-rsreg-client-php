<?php

declare(strict_types=1);

namespace RNIDS\Xml;

use RNIDS\Exception\ProtocolExceptionFactory;
use RNIDS\Xml\Response\ResponseMetadata;

final class ResultCodePolicy
{
    public static function assertSuccess(ResponseMetadata $metadata): void
    {
        if ($metadata->isSuccess()) {
            return;
        }

        throw ProtocolExceptionFactory::fromMetadata($metadata);
    }
}
