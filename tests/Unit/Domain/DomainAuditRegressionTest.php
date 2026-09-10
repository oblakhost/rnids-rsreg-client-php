<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\DomainService;

#[Group('unit')]
final class DomainAuditRegressionTest extends TestCase
{
    /** @return array{keyTag: int, alg: int, digestType: int, digest: string} */
    private static function record(): array
    {
        return [ 'keyTag' => 12345, 'alg' => 8, 'digestType' => 2, 'digest' => \str_repeat('AB', 32) ];
    }

    public function testNameserverUpdateIncludesGlueAndRemoval(): void
    {
        $transport = new DomainAuditTransport();
        (new DomainService($transport))->update([
            'add' => ['nameservers' => [['name' => 'ns2.example.rs', 'addresses' => ['192.0.2.2', '2001:db8::2']]]],
            'name' => 'example.rs',
            'remove' => ['nameservers' => ['ns1.example.rs']],
        ]);
        self::assertStringContainsString(
            '<domain:hostAddr ip="v4">192.0.2.2</domain:hostAddr>',
            $transport->written,
        );
        self::assertStringContainsString(
            '<domain:hostAddr ip="v6">2001:db8::2</domain:hostAddr>',
            $transport->written,
        );
        self::assertStringContainsString(
            '<domain:rem><domain:ns><domain:hostObj>ns1.example.rs',
            $transport->written,
        );
    }

    public function testUnknownSectionKeyCannotBeSilentlyDropped(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DomainService(new DomainAuditTransport()))->update([
            'add' => ['statuses' => ['clientHold'], 'nameserver' => ['ns2.example.rs']],
            'name' => 'example.rs',
        ]);
    }

    public function testUnknownNameserverGlueKeyIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DomainService(new DomainAuditTransport()))->update([
            'name' => 'example.rs',
            'add' => ['nameservers' => [['name' => 'ns1.example.rs', 'address' => ['192.0.2.1']]]],
        ]);
    }

    public function testUnknownStructuredAddressKeyIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DomainService(new DomainAuditTransport()))->update([
            'name' => 'example.rs',
            'add' => ['nameservers' => [['name' => 'ns1.example.rs', 'addresses' => [
                ['address' => '192.0.2.1', 'ipVersion' => 'v4', 'unexpected' => true],
            ]]]],
        ]);
    }

    public function testRegistrantChangeCannotDiscardNameserverChanges(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DomainService(new DomainAuditTransport()))->update([
            'name' => 'example.rs', 'registrant' => 'REG-2',
            'add' => ['nameservers' => ['ns2.example.rs']],
        ]);
    }

    public function testRegistrantChangeCannotDiscardPrivacyChanges(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DomainService(new DomainAuditTransport()))->update([
            'name' => 'example.rs', 'registrant' => 'REG-2',
            'extension' => ['isWhoisPrivacy' => true],
        ]);
    }

    public function testUpdateRejectsUnsupportedOperationMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be "normal" or "secure"');
        (new DomainService(new DomainAuditTransport()))->update([
            'name' => 'example.rs', 'extension' => ['operationMode' => 'direct'],
        ]);
    }

    public function testPrivacyOnlyUpdateIsSent(): void
    {
        $transport = new DomainAuditTransport();
        (new DomainService($transport))->update(
            [ 'name' => 'example.rs', 'extension' => [ 'isWhoisPrivacy' => false ] ],
        );
        self::assertStringContainsString(
            '<domainExt:isWhoisPrivacy>false</domainExt:isWhoisPrivacy>',
            $transport->written,
        );
    }

    public function testEmptyExtensionIsNotAMutation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DomainService(new DomainAuditTransport()))->update(
            [ 'name' => 'example.rs', 'extension' => [] ],
        );
    }

    public function testInfoPreservesSubordinateHostsPrivacyExpiryAndDsRecords(): void
    {
        $transport = new DomainAuditTransport();
        $transport->data = '<resData><domain:infData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
            . '<domain:name>example.rs</domain:name><domain:host>ns1.example.rs</domain:host>'
            . '<domain:host>ns2.example.rs</domain:host></domain:infData></resData>'
            . '<extension><domainExt:domain-ext xmlns:domainExt="http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0">'
            . '<domainExt:whoisPrivacyPaidUntil>2027-01-01T00:00:00Z</domainExt:whoisPrivacyPaidUntil>'
            . '</domainExt:domain-ext><secDNS:infData xmlns:secDNS="urn:ietf:params:xml:ns:secDNS-1.1">'
            . '<secDNS:dsData><secDNS:keyTag>12345</secDNS:keyTag><secDNS:alg>8</secDNS:alg>'
            . '<secDNS:digestType>2</secDNS:digestType><secDNS:digest>' . \str_repeat('AB', 32)
            . '</secDNS:digest></secDNS:dsData></secDNS:infData></extension>';
        $info = (new DomainService($transport))->info('example.rs', 'sub');
        self::assertSame([ 'ns1.example.rs', 'ns2.example.rs' ], $info['hosts']);
        self::assertSame([], $info['nameservers']);
        self::assertSame('2027-01-01', $info['whoisPrivacyPaidUntil']->format('Y-m-d'));
        self::assertEquals([ self::record() ], $info['dnssec']['records']);
    }

    public function testRegisterCombinesRnidsAndDnssecInOneExtension(): void
    {
        $transport = new DomainAuditTransport();
        (new DomainService($transport))->register([
            'contacts' => [['type' => 'admin', 'handle' => 'ADM-1'], ['type' => 'tech', 'handle' => 'TEC-1']],
            'dnssec' => ['records' => [self::record()]],
            'extension' => ['isWhoisPrivacy' => true],
            'name' => 'example.rs',
            'registrant' => 'REG-1',
        ]);
        self::assertSame(1, \substr_count($transport->written, '<extension>'));
        self::assertStringContainsString(
            '<secDNS:create xmlns:secDNS="urn:ietf:params:xml:ns:secDNS-1.1">',
            $transport->written,
        );
        self::assertStringContainsString('<secDNS:keyTag>12345</secDNS:keyTag>', $transport->written);
    }

    public function testDnssecRolloverRemovesBeforeAdding(): void
    {
        $transport = new DomainAuditTransport();
        (new DomainService($transport))->update([
            'dnssec' => ['remove' => [self::record()], 'add' => [self::record()]],
            'name' => 'example.rs',
        ]);
        self::assertStringContainsString('</secDNS:rem><secDNS:add>', $transport->written);
        self::assertSame(2, \substr_count($transport->written, '<secDNS:dsData>'));
    }

    public function testDnssecRemoveAll(): void
    {
        $transport = new DomainAuditTransport();
        (new DomainService($transport))->update(
            [ 'name' => 'example.rs', 'dnssec' => [ 'removeAll' => true ] ],
        );
        self::assertStringContainsString(
            '<secDNS:rem><secDNS:all>true</secDNS:all></secDNS:rem>',
            $transport->written,
        );
    }

    public function testMixedDnssecAndBaseUpdateIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DomainService(new DomainAuditTransport()))->update([
        'authInfo' => 'new-code',
        'dnssec' => ['removeAll' => true],
        'name' => 'example.rs',
        ]);
    }

    public function testUnsupportedDnssecOptionIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DomainService(new DomainAuditTransport()))->update([
            'dnssec' => ['add' => [self::record()], 'urgent' => true],
            'name' => 'example.rs',
        ]);
    }

    public function testTransferOperationNamesAreDiscoverable(): void
    {
        foreach ([ 'Request', 'Query', 'Approve', 'Cancel', 'Reject' ] as $operation) {
            $transport = new DomainAuditTransport();
            $service = new DomainService($transport);
            $service->{'transfer' . $operation}('example.rs', 'transfer-code');
            self::assertStringContainsString(
                '<transfer op="' . \strtolower($operation) . '">',
                $transport->written,
            );
            self::assertStringContainsString('<domain:pw>transfer-code</domain:pw>', $transport->written);
        }
    }
}
