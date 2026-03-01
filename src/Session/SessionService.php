<?php

declare(strict_types=1);

namespace RNIDS\Session;

use RNIDS\Connection\Transport;
use RNIDS\Xml\ClTrid\ClTridGenerator;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use RNIDS\Xml\CommandExecutor;
use RNIDS\Xml\Response\LastResponseMetadata;
use RNIDS\Xml\Session\HelloRequestBuilder;
use RNIDS\Xml\Session\HelloResponseParser;
use RNIDS\Xml\Session\LoginRequestBuilder;
use RNIDS\Xml\Session\LoginResponseParser;
use RNIDS\Xml\Session\LogoutRequestBuilder;
use RNIDS\Xml\Session\LogoutResponseParser;
use RNIDS\Xml\Session\PollRequestBuilder;
use RNIDS\Xml\Session\PollResponseParser;

final class SessionService
{
    private CommandExecutor $executor;

    private ClTridGenerator $tridGenerator;

    private SessionInputNormalizer $inputNormalizer;

    private SessionResponseMapper $responseMapper;

    private HelloRequestBuilder $helloRequestBuilder;

    private HelloResponseParser $helloResponseParser;

    private LoginRequestBuilder $loginRequestBuilder;

    private LoginResponseParser $loginResponseParser;

    private LogoutRequestBuilder $logoutRequestBuilder;

    private LogoutResponseParser $logoutResponseParser;

    private PollRequestBuilder $pollRequestBuilder;

    private PollResponseParser $pollResponseParser;

    /**
     * Creates a session service for hello/login/logout/poll command flows.
     *
     * @param Transport $transport Connected transport used to send and receive EPP frames.
     * @param CommandExecutor|null $executor Optional command executor override for tests.
     * @param ClTridGenerator|null $tridGenerator Optional client transaction id generator override.
     * @param LastResponseMetadata|null $lastResponseMetadata Optional shared holder for last parsed response metadata.
     * @param SessionInputNormalizer|null $inputNormalizer Optional input normalizer override.
     * @param SessionResponseMapper|null $responseMapper Optional response mapper override.
     */
    public function __construct(
        Transport $transport,
        ?CommandExecutor $executor = null,
        ?ClTridGenerator $tridGenerator = null,
        ?LastResponseMetadata $lastResponseMetadata = null,
        ?SessionInputNormalizer $inputNormalizer = null,
        ?SessionResponseMapper $responseMapper = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport, null, $lastResponseMetadata);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('SESSION');
        $this->inputNormalizer = $inputNormalizer ?? new SessionInputNormalizer();
        $this->responseMapper = $responseMapper ?? new SessionResponseMapper();
        $this->helloRequestBuilder = new HelloRequestBuilder();
        $this->helloResponseParser = new HelloResponseParser();
        $this->loginRequestBuilder = new LoginRequestBuilder();
        $this->loginResponseParser = new LoginResponseParser();
        $this->logoutRequestBuilder = new LogoutRequestBuilder();
        $this->logoutResponseParser = new LogoutResponseParser();
        $this->pollRequestBuilder = new PollRequestBuilder();
        $this->pollResponseParser = new PollResponseParser();
    }

    /**
     * Performs EPP login and initializes an authenticated session state.
     *
     * @param array{
     *   clientId: non-empty-string,
     *   password: non-empty-string,
     *   version?: non-empty-string,
     *   language?: non-empty-string,
     *   objectUris?: list<non-empty-string>,
     *   extensionUris?: list<non-empty-string>
     * } $request Session login payload with credentials and optional service menu declarations.
     *
     * @return array{} Empty array on successful login command completion.
     */
    public function login(array $request): array
    {
        $xml = $this->loginRequestBuilder->build(
            $this->inputNormalizer->buildLoginRequest($request),
            $this->tridGenerator->nextId(),
        );

        $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->loginResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapEmptyResponse();
    }

    /**
     * Requests server greeting data and supported object/extension capabilities.
     *
     * @return array{
     *   extensionUris: list<string>,
     *   languages: list<string>,
     *   objectUris: list<string>,
     *   serverDate: string|null,
     *   serverId: string|null,
     *   versions: list<string>
     * } Parsed hello response payload with server identity and supported protocol menu.
     */
    public function hello(): array
    {
        $response = $this->executor->execute(
            $this->helloRequestBuilder->build(),
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->helloResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapHelloResponse($response);
    }

    /**
     * Performs EPP logout and closes the authenticated server session.
     *
     * @return array{} Empty array on successful logout command completion.
     */
    public function logout(): array
    {
        $xml = $this->logoutRequestBuilder->build($this->tridGenerator->nextId());

        $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->logoutResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapEmptyResponse();
    }

    /**
     * Executes a poll request to fetch queue data or acknowledge a queue message.
     *
     * @param array{messageId?: mixed, operation?: mixed} $request
     *
     * @return array{
     *   count: int|null,
     *   domainTransferData: array{
     *     actionClientId: string|null,
     *     actionDate: string|null,
     *     expirationDate: string|null,
     *     name: string|null,
     *     requestClientId: string|null,
     *     requestDate: string|null,
     *     transferStatus: string|null,
     *   }|null,
     *   message: string|null,
     *   messageId: string|null,
     *   queueDate: string|null
     * } Poll queue metadata including message details for req/ack operations.
     */
    public function poll(array $request = []): array
    {
        $xml = $this->pollRequestBuilder->build(
            $this->inputNormalizer->buildPollRequest($request),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                $this->pollResponseParser->parse($responseXml, $metadata),
        );

        return $this->responseMapper->mapPollResponse($response);
    }
}
