<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RNIDS\Cli\Application;
use RNIDS\Cli\Environment;
use RNIDS\Config\ClientConfigFactory;
use Tests\Support\CliPeerTransport;

final class ApplicationTest extends TestCase
{
    /** @var array<string, string> */
    private array $originalEnvironment = [];

    protected function setUp(): void
    {
        foreach (getenv() as $name => $value) {
            if (str_starts_with($name, 'RNIDS_EPP_')) {
                $this->originalEnvironment[$name] = $value;
                putenv($name);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (getenv() as $name => $value) {
            if (str_starts_with($name, 'RNIDS_EPP_')) {
                putenv($name);
            }
        }
        foreach ($this->originalEnvironment as $name => $value) {
            putenv($name . '=' . $value);
        }
    }

    public function testExecutableHelpSucceedsWithoutConfiguration(): void
    {
        $pipes = [];
        $process = proc_open([PHP_BINARY, 'bin/rsreg', '--help'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 3));
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr);
        self::assertStringContainsString('Usage:', $stdout);
        self::assertStringContainsString('unaffiliated', $stdout);
        self::assertSame('', $stderr);
    }

    public function testEnvironmentDefaultsAuthenticateTlsAndUseRnidsCompatibility(): void
    {
        $this->credentials();
        $config = ClientConfigFactory::fromArray(Environment::clientConfig());
        self::assertSame('epp-test.rnids.rs', $config->connectionConfig->hostname);
        self::assertSame('hello', $config->greetingMode);
        self::assertFalse($config->requireClientTransactionId);
        self::assertTrue($config->tlsConfig->verifyPeer);
        self::assertTrue($config->tlsConfig->verifyPeerName);
        self::assertFalse($config->tlsConfig->allowSelfSigned);
        self::assertNull($config->tlsConfig->clientCertificatePassword);
        self::assertNull($config->tlsConfig->caFilePath);
    }

    public function testEnvironmentSupportsExplicitEndpointAndTlsOverrides(): void
    {
        $this->credentials();
        putenv('RNIDS_EPP_HOST=localhost');
        putenv('RNIDS_EPP_PORT=1700');
        putenv('RNIDS_EPP_CONNECT_TIMEOUT=3');
        putenv('RNIDS_EPP_READ_TIMEOUT=4');
        putenv('RNIDS_EPP_GREETING_MODE=unsolicited');
        putenv('RNIDS_EPP_REQUIRE_CLIENT_TRANSACTION_ID=true');
        putenv('RNIDS_EPP_TLS_ALLOW_SELF_SIGNED=yes');
        putenv('RNIDS_EPP_TLS_VERIFY_PEER=off');
        putenv('RNIDS_EPP_TLS_VERIFY_PEER_NAME=0');
        putenv('RNIDS_EPP_TLS_PEER_NAME=registry.example');
        putenv('RNIDS_EPP_CLIENT_CERT_PASSWORD=provided-password');
        putenv('RNIDS_EPP_CA_CERT_PATH=' . __FILE__);
        $config = ClientConfigFactory::fromArray(Environment::clientConfig());
        self::assertSame('localhost', $config->connectionConfig->hostname);
        self::assertSame(1700, $config->connectionConfig->port);
        self::assertSame(3, $config->connectionConfig->connectTimeoutSeconds);
        self::assertSame(4, $config->connectionConfig->readTimeoutSeconds);
        self::assertSame('unsolicited', $config->greetingMode);
        self::assertTrue($config->requireClientTransactionId);
        self::assertTrue($config->tlsConfig->allowSelfSigned);
        self::assertFalse($config->tlsConfig->verifyPeer);
        self::assertFalse($config->tlsConfig->verifyPeerName);
        self::assertSame('registry.example', $config->tlsConfig->peerName);
        self::assertSame('provided-password', $config->tlsConfig->clientCertificatePassword);
        self::assertSame(__FILE__, $config->tlsConfig->caFilePath);
    }

    public function testCustomEndpointUsesStrictCoreProtocolDefaults(): void
    {
        $this->credentials();
        putenv('RNIDS_EPP_HOST=epp.registry.example');
        $config = ClientConfigFactory::fromArray(Environment::clientConfig());
        self::assertSame('unsolicited', $config->greetingMode);
        self::assertTrue($config->requireClientTransactionId);
    }

    public function testRenewJsonTranslatesScalarArgumentsAndClosesSession(): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([CliPeerTransport::response(
            '<domain:renData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"><domain:name>example.rs</domain:name>'
            . '<domain:exDate>2028-09-12T00:00:00Z</domain:exDate></domain:renData>',
        )]);
        $result = (new Application($peer))->run(['domain:renew', '{"name":"example.rs","years":2,"expiry":"2026-09-12"}']);
        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertSame('example.rs', json_decode($result['stdout'], true)['domain']);
        self::assertStringContainsString('<domain:curExpDate>2026-09-12</domain:curExpDate>', $peer->requests[2]);
        self::assertStringContainsString('<domain:period unit="y">2</domain:period>', $peer->requests[2]);
        self::assertStringContainsString('<hello/>', $peer->requests[0]);
        self::assertStringContainsString('<logout/>', $peer->requests[3]);
        self::assertFalse($peer->connected);
    }

    public function testRenewWithoutExpiryUsesDomainInfo(): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([
            CliPeerTransport::response('<domain:infData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
                . '<domain:name>example.rs</domain:name><domain:exDate>2026-09-12T00:00:00Z</domain:exDate></domain:infData>'),
            CliPeerTransport::response('<domain:renData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
                . '<domain:name>example.rs</domain:name><domain:exDate>2027-09-12T00:00:00Z</domain:exDate></domain:renData>'),
        ]);
        $result = (new Application($peer))->run(['domain:renew', '{"name":"example.rs"}']);
        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('<domain:info', $peer->requests[2]);
        self::assertStringContainsString('<domain:curExpDate>2026-09-12</domain:curExpDate>', $peer->requests[3]);
        self::assertStringContainsString('<domain:period unit="y">1</domain:period>', $peer->requests[3]);
    }

    /** @return list<array{string, string, string}> */
    public static function transferCommands(): array
    {
        $cases = [];
        foreach (['request', 'query', 'approve', 'cancel', 'reject'] as $action) {
            $cases[] = ['domain:transfer', '{"name":"example.rs","authInfo":"secret","type":"' . $action . '"}', $action];
            $cases[] = ['domain:transfer:' . $action, '{"name":"example.rs","authInfo":"secret"}', $action];
        }
        return $cases;
    }

    #[DataProvider('transferCommands')]
    public function testTransferDispatchesExplicitEppAction(string $command, string $payload, string $action): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([CliPeerTransport::response(
            '<domain:trnData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
            . '<domain:name>example.rs</domain:name><domain:trStatus>pending</domain:trStatus></domain:trnData>',
        )]);
        $result = (new Application($peer))->run([$command, $payload]);
        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertSame('pending', json_decode($result['stdout'], true)['transferStatus']);
        self::assertStringContainsString('<transfer op="' . $action . '">', $peer->requests[2]);
        self::assertStringContainsString('<domain:pw>secret</domain:pw>', $peer->requests[2]);
        self::assertFalse($peer->connected);
    }

    /** @return list<array{list<string>}> */
    public static function invalidCommands(): array
    {
        return [
            [['unsupported', 'anything']],
            [['domain:renew']],
            [['domain:renew', '[]']],
            [['domain:renew', '{broken']],
            [['domain:renew', '{"name":"example.rs","years":"2"}']],
            [['domain:transfer', '{"name":"example.rs","type":"unknown"}']],
            [['domain:transfer:query', '{"name":"example.rs","type":"request"}']],
            [['domain:check', ', ,']],
            [['session:poll', 'unexpected']],
            [['contact:update', '{"id":"CID-1","change":{"email":"new@example.rs"},"email":"conflict@example.rs"}']],
            [['contact:update', '{"id":"CID-1","change":{"id":"CID-2"}}']],
            [['contact:update', '{"id":"CID-1","change":[]}']],
            [['domain:renew', '{"name":"example.rs","expiry":123}']],
            [['domain:renew', '{"name":""}']],
            [['domain:transfer:request', '{"name":"example.rs","authInfo":42}']],
            [['domain:delete', ' ']],
            [[]],
            [['domain:register', '{"name":"example.rs","years":2,"period":3}']],
            [['domain:register', '{"name":"example.rs","years":2,"periodUnit":"m"}']],
            [['domain:register', '{"name":"example.rs","years":"2"}']],
        ];
    }

    /** @param list<string> $arguments */
    #[DataProvider('invalidCommands')]
    public function testInvalidInputFailsBeforeConnecting(array $arguments): void
    {
        $this->credentials();
        $peer = new CliPeerTransport();
        $result = (new Application($peer))->run($arguments);
        self::assertSame(2, $result['exitCode'], $result['stderr']);
        self::assertSame('', $result['stdout']);
        self::assertNotSame('', $result['stderr']);
        self::assertSame([], $peer->requests);
    }

    public function testRegistryFailureClosesSessionAndReportsError(): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([CliPeerTransport::response('', 2303)]);
        $result = (new Application($peer))->run(['domain:delete', 'example.rs']);
        self::assertSame(1, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('domain:delete failed:', $result['stderr']);
        self::assertStringContainsString('<logout/>', $peer->requests[3]);
        self::assertFalse($peer->connected);
    }

    public function testCommandAndCleanupErrorsAreBothReported(): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([CliPeerTransport::response('', 2303)]);
        $peer->logoutCode = 2400;
        $result = (new Application($peer))->run(['domain:delete', 'example.rs']);
        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('domain:delete failed:', $result['stderr']);
        self::assertStringContainsString('Session close failed:', $result['stderr']);
        self::assertFalse($peer->connected);
    }

    public function testTransferQueryAllowsOmittingAuthorization(): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([CliPeerTransport::response()]);
        $result = (new Application($peer))->run(['domain:transfer:query', '{"name":"example.rs"}']);
        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('<transfer op="query">', $peer->requests[2]);
        self::assertStringNotContainsString('<domain:authInfo>', $peer->requests[2]);
    }

    /** @return list<array{string}> */
    public static function invalidEnvironment(): array
    {
        return [
            ['RNIDS_EPP_USERNAME'],
            ['RNIDS_EPP_PASSWORD'],
            ['RNIDS_EPP_CLIENT_CERT_PATH'],
            ['RNIDS_EPP_CLIENT_CERT_PATH=/missing/cli-certificate.pem'],
            ['RNIDS_EPP_CA_CERT_PATH=/missing/cli-ca.pem'],
            ['RNIDS_EPP_PORT=65536'],
            ['RNIDS_EPP_CONNECT_TIMEOUT=0'],
            ['RNIDS_EPP_READ_TIMEOUT=twenty'],
            ['RNIDS_EPP_TLS_VERIFY_PEER=disable'],
            ['RNIDS_EPP_GREETING_MODE=automatic'],
        ];
    }

    #[DataProvider('invalidEnvironment')]
    public function testInvalidConfigurationFailsWithoutConnecting(string $assignment): void
    {
        $this->credentials();
        putenv($assignment);
        $peer = new CliPeerTransport();
        $result = (new Application($peer))->run(['session:poll']);
        self::assertSame(2, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString(explode('=', $assignment)[0], $result['stderr']);
        self::assertSame([], $peer->requests);
    }

    public function testTlsDiagnosticsDescribeVerificationWithoutPrintingPasswords(): void
    {
        $this->credentials();
        putenv('RNIDS_EPP_CLIENT_CERT_PASSWORD=private-key-password');
        putenv('RNIDS_EPP_TLS_DEBUG=true');
        $peer = new CliPeerTransport([CliPeerTransport::response('', 1300)]);
        $result = (new Application($peer))->run(['session:poll']);
        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('verifyPeer=true verifyPeerName=true', $result['stderr']);
        self::assertStringNotContainsString('private-key-password', $result['stderr']);
        self::assertStringNotContainsString('test-password', $result['stderr']);
        self::assertIsArray(json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR));
    }

    public function testCleanupFailureKeepsSuccessfulCommandOutput(): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([CliPeerTransport::response()]);
        $peer->logoutCode = 2400;
        $result = (new Application($peer))->run(['domain:delete', 'example.rs']);
        self::assertSame(1, $result['exitCode']);
        self::assertSame([], json_decode($result['stdout'], true));
        self::assertStringContainsString('Session close failed:', $result['stderr']);
        self::assertFalse($peer->connected);
    }

    public function testAdvertisedContactChangePayloadReachesTheSdk(): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([CliPeerTransport::response()]);
        $result = (new Application($peer))->run(['contact:update', '{"id":"CID-12345","change":{"email":"new@example.rs"}}']);
        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('<contact:email>new@example.rs</contact:email>', $peer->requests[2]);
        self::assertSame([], json_decode($result['stdout'], true));
    }

    public function testAdvertisedRegisterYearsPayloadSetsTheRegistrationPeriod(): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([CliPeerTransport::response(
            '<domain:creData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">'
            . '<domain:name>example.rs</domain:name></domain:creData>',
        )]);
        $result = (new Application($peer))->run(['domain:register', '{"name":"example.rs","registrant":"REG","years":2,"contacts":[{"type":"admin","handle":"ADMIN"},{"type":"tech","handle":"TECH"}]}']);
        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('<domain:period unit="y">2</domain:period>', $peer->requests[2]);
    }

    /** @return list<array{list<string>, string, string}> */
    public static function standardCommands(): array
    {
        return [
            [['session:hello'], '', '<hello/>'],
            [['session:poll'], '', '<poll op="req"/>'],
            [['domain:check', ' example.rs, test.rs '], '<domain:chkData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"><domain:cd><domain:name avail="1">example.rs</domain:name></domain:cd></domain:chkData>', '<domain:name>test.rs</domain:name>'],
            [['domain:info', 'example.rs'], '<domain:infData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"><domain:name>example.rs</domain:name></domain:infData>', '<domain:info'],
            [['domain:register', '{"name":"example.rs","registrant":"REG","period":2,"contacts":[{"type":"admin","handle":"ADMIN"},{"type":"tech","handle":"TECH"}]}'], '<domain:creData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"><domain:name>example.rs</domain:name></domain:creData>', '<domain:period unit="y">2</domain:period>'],
            [['domain:update', '{"name":"example.rs","add":{"statuses":["clientHold"]}}'], '', '<domain:status s="clientHold"/>'],
            [['contact:check', 'CID-1,CID-2'], '<contact:chkData xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"><contact:cd><contact:id avail="1">CID-1</contact:id></contact:cd></contact:chkData>', '<contact:id>CID-2</contact:id>'],
            [['contact:info', 'CID-1'], '<contact:infData xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"><contact:id>CID-1</contact:id></contact:infData>', '<contact:info'],
            [['contact:create', '{"id":"OBL-CLI","postalInfo":{"name":"John Doe","address":{"streets":["123 Main St"],"city":"Belgrade","countryCode":"RS"}},"email":"john@example.rs"}'], '<contact:creData xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"><contact:id>OBL-CLI</contact:id></contact:creData>', '<contact:id>OBL-CLI</contact:id>'],
            [['contact:update', '{"id":"CID-1","email":"updated@example.rs"}'], '', '<contact:email>updated@example.rs</contact:email>'],
            [['contact:delete', 'CID-1'], '', '<contact:delete'],
            [['host:check', 'ns1.example.rs,ns2.example.rs'], '<host:chkData xmlns:host="urn:ietf:params:xml:ns:host-1.0"><host:cd><host:name avail="1">ns1.example.rs</host:name></host:cd></host:chkData>', '<host:name>ns2.example.rs</host:name>'],
            [['host:info', 'ns1.example.rs'], '<host:infData xmlns:host="urn:ietf:params:xml:ns:host-1.0"><host:name>ns1.example.rs</host:name></host:infData>', '<host:info'],
            [['host:create', '{"name":"ns1.example.rs","addresses":[{"address":"192.0.2.7","ipVersion":"v4"}]}'], '<host:creData xmlns:host="urn:ietf:params:xml:ns:host-1.0"><host:name>ns1.example.rs</host:name></host:creData>', '<host:addr ip="v4">192.0.2.7</host:addr>'],
            [['host:update', '{"name":"ns1.example.rs","add":{"addresses":[{"address":"2001:db8::7","ipVersion":"v6"}]}}'], '', '<host:addr ip="v6">2001:db8::7</host:addr>'],
            [['host:delete', 'ns1.example.rs'], '', '<host:delete'],
        ];
    }

    /** @param list<string> $arguments */
    #[DataProvider('standardCommands')]
    public function testStandardCommandsUseSdkXmlAndReturnJson(array $arguments, string $data, string $expectedXml): void
    {
        $this->credentials();
        $peer = new CliPeerTransport([CliPeerTransport::response($data, $arguments[0] === 'session:poll' ? 1300 : 1000)]);
        $result = (new Application($peer))->run($arguments);
        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertIsArray(json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR));
        self::assertStringContainsString($expectedXml, $peer->requests[2]);
        self::assertStringContainsString('<logout/>', $peer->requests[3]);
        self::assertFalse($peer->connected);
    }

    private function credentials(): void
    {
        putenv('RNIDS_EPP_USERNAME=cli-test');
        putenv('RNIDS_EPP_PASSWORD=test-password');
        putenv('RNIDS_EPP_CLIENT_CERT_PATH=' . __FILE__);
    }
}
