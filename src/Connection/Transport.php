<?php

declare(strict_types=1);

namespace RNIDS\Connection;

interface Transport
{
    public function connect(): void;

    public function disconnect(): void;

    public function writeFrame(string $payload): void;

    public function readFrame(): string;
}
