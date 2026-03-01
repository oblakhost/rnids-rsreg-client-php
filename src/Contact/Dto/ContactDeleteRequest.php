<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

final class ContactDeleteRequest
{
    public function __construct(public readonly string $id)
    {
    }
}
