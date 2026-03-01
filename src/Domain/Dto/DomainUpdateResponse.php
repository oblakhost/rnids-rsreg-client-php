<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Represents parsed data from a successful EPP domain update response.
 */
final class DomainUpdateResponse
{
    /**
     * @param ResponseMetadata $metadata Parsed metadata from update response.
     */
    public function __construct(public readonly ResponseMetadata $metadata)
    {
    }
}
