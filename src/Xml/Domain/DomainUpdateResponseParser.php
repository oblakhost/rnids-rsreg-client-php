<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainUpdateResponse;
use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Maps domain update response metadata to a typed DTO.
 */
final class DomainUpdateResponseParser
{
    /**
     * @param string $xml Raw response XML payload.
     * @param ResponseMetadata $metadata Parsed response metadata.
     */
    public function parse(string $xml, ResponseMetadata $metadata): DomainUpdateResponse
    {
        return new DomainUpdateResponse($metadata);
    }
}
