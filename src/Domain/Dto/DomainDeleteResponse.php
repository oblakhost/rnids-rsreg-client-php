<?php

declare(strict_types=1);

namespace RNIDS\Domain\Dto;

use RNIDS\Xml\Response\ResponseMetadata;

/**
 * Represents parsed data from a successful EPP domain delete response.
 */
final class DomainDeleteResponse
{
    /**
     * @param ResponseMetadata $metadata Parsed metadata from delete response.
     */
    public function __construct(public readonly ResponseMetadata $metadata)
    {
    }
}
