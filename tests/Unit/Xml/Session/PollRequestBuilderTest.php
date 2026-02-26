<?php

declare(strict_types=1);

namespace Tests\Unit\Xml\Session;

use PHPUnit\Framework\TestCase;
use RNIDS\Session\Dto\PollRequest;
use RNIDS\Xml\Session\PollRequestBuilder;

final class PollRequestBuilderTest extends TestCase
{
    public function testBuildCreatesPollRequestXmlForReqOperation(): void
    {
        $xml = (new PollRequestBuilder())->build(new PollRequest('req'), 'TRID-1');

        self::assertStringContainsString('<poll op="req"/>', $xml);
        self::assertStringContainsString('<clTRID>TRID-1</clTRID>', $xml);
    }

    public function testBuildCreatesPollRequestXmlForAckOperationWithMessageId(): void
    {
        $xml = (new PollRequestBuilder())->build(new PollRequest('ack', 'MSG<&>'), 'TRID-2');

        self::assertStringContainsString('<poll op="ack" msgID="MSG&lt;&amp;&gt;"/>', $xml);
        self::assertStringContainsString('<clTRID>TRID-2</clTRID>', $xml);
    }
}
