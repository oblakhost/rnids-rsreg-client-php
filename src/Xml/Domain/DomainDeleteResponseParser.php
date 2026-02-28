<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainDeleteResponse;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Maps domain delete response metadata to a typed DTO.
 */
final class DomainDeleteResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): DomainDeleteResponse
    {
        return new DomainDeleteResponse($metadata);
    }
}
