<?php

declare(strict_types=1);

namespace RNIDS;

use RNIDS\Connection\Transport;

final class Client
{
    private Builder $builder;

    public function __construct(?Builder $builder = null)
    {
        $this->builder = $builder ?? new Builder();
    }

    public function transport(): Transport
    {
        return $this->builder->buildTransport();
    }
}
