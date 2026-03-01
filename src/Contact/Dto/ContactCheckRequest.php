<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

final class ContactCheckRequest
{
    /**
     * @param list<string> $ids
     */
    public function __construct(public readonly array $ids)
    {
    }
}
