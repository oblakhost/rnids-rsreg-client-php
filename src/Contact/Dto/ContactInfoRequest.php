<?php

declare(strict_types=1);

namespace RNIDS\Contact\Dto;

final class ContactInfoRequest
{
    public function __construct(public readonly string $id)
    {
    }
}
