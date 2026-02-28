<?php

declare(strict_types=1);

namespace RNIDS\Xml;

use RNIDS\Connection\Transport;
use RNIDS\Exception\TransportException;
use RNIDS\Xml\Response\LastResponseMetadata;
use RNIDS\Xml\Response\ResponseMetadata;
use RNIDS\Xml\Response\ResponseMetadataParser;

final class CommandExecutor
{
    private ResponseMetadataParser $responseMetadataParser;

    /**
     * @param ResponseMetadataParser|null $responseMetadataParser Optional parser override for tests.
     */
    public function __construct(
        private readonly Transport $transport,
        ?ResponseMetadataParser $responseMetadataParser = null,
        private readonly ?LastResponseMetadata $lastResponseMetadata = null,
    ) {
        $this->responseMetadataParser = $responseMetadataParser ?? new ResponseMetadataParser();
    }

    /**
     * @template T
     *
     * @param callable(string, ResponseMetadata): T $responseParser
     *
     * @return T
     */
    public function execute(string $xml, callable $responseParser)
    {
        $responseXml = $this->sendAndReceive($xml);
        $metadata = $this->responseMetadataParser->parse($responseXml);
        $this->lastResponseMetadata?->set($metadata);
        ResultCodePolicy::assertSuccess($metadata);

        return $responseParser($responseXml, $metadata);
    }

    private function sendAndReceive(string $xml): string
    {
        try {
            $this->transport->writeFrame($xml);

            return $this->transport->readFrame();
        } catch (\RuntimeException $exception) {
            throw new \RNIDS\Exception\TransportException(
                $exception->getMessage(),
                (int) $exception->getCode(),
                $exception,
            );
        }
    }
}
