<?php

declare(strict_types=1);

namespace Tests\Unit\Xml;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Connection\Transport;
use RNIDS\Xml\CommandExecutor;
use RNIDS\Xml\Response\LastResponseMetadata;

#[Group('unit')]
final class CommandExecutorTest extends TestCase
{
    public function testExecuteStoresLastResponseMetadataInSharedHolder(): void
    {
        $transport = new class () implements Transport {
            public function connect(): void
            {
                // Not needed for this unit test.
            }

            public function disconnect(): void
            {
                // Not needed for this unit test.
            }

            public function writeFrame(string $payload): void
            {
                // Not needed for this unit test.
            }

            public function readFrame(): string
            {
                return '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">'
                    . '<response>'
                    . '<result code="1000"><msg>Command completed successfully</msg></result>'
                    . '<trID><clTRID>CL-1</clTRID><svTRID>SV-1</svTRID></trID>'
                    . '</response>'
                    . '</epp>';
            }
        };

        $lastResponseMetadata = new LastResponseMetadata();
        $executor = new CommandExecutor($transport, null, $lastResponseMetadata);

        $executor->execute(
            '<epp/>',
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata): int =>
                $metadata->resultCode,
        );

        $metadata = $lastResponseMetadata->get();

        self::assertNotNull($metadata);
        self::assertSame(1000, $metadata->resultCode);
        self::assertSame('Command completed successfully', $metadata->message);
        self::assertSame('CL-1', $metadata->clientTransactionId);
        self::assertSame('SV-1', $metadata->serverTransactionId);
    }
}
