<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Connection\Transport;
use RNIDS\Domain\Dto\DomainCheckRequest;
use RNIDS\Xml\ClTrid\ClTridGenerator;
use RNIDS\Xml\ClTrid\IncrementalClTridGenerator;
use RNIDS\Xml\CommandExecutor;
use RNIDS\Xml\Domain\DomainCheckRequestBuilder;
use RNIDS\Xml\Domain\DomainCheckResponseParser;

final class DomainService
{
    private CommandExecutor $executor;

    private ClTridGenerator $tridGenerator;

    public function __construct(
        Transport $transport,
        ?CommandExecutor $executor = null,
        ?ClTridGenerator $tridGenerator = null,
    ) {
        $this->executor = $executor ?? new CommandExecutor($transport);
        $this->tridGenerator = $tridGenerator ?? new IncrementalClTridGenerator('DOMAIN');
    }

    /**
     * @param array{names?: mixed} $request
     *
     * @return array{
     *   metadata: array{
     *     clientTransactionId: string|null,
     *     message: string,
     *     resultCode: int,
     *     serverTransactionId: string|null
     *   },
     *   items: list<array{name: string, available: bool, reason: string|null}>
     * }
     */
    public function check(array $request): array
    {
        $xml = (new DomainCheckRequestBuilder())->build(
            new DomainCheckRequest($this->requireNames($request)),
            $this->tridGenerator->nextId(),
        );

        $response = $this->executor->execute(
            $xml,
            static fn(string $responseXml, \RNIDS\Xml\Response\ResponseMetadata $metadata) =>
                (new DomainCheckResponseParser())->parse($responseXml, $metadata),
        );

        return [
            'items' => \array_map(
                static fn(\RNIDS\Domain\Dto\DomainCheckItem $item): array => [
                    'available' => $item->available,
                    'name' => $item->name,
                    'reason' => $item->reason,
                ],
                $response->items,
            ),
            'metadata' => [
                'clientTransactionId' => $response->metadata->clientTransactionId,
                'message' => $response->metadata->message,
                'resultCode' => $response->metadata->resultCode,
                'serverTransactionId' => $response->metadata->serverTransactionId,
            ],
        ];
    }

    /**
     * @param array{names?: mixed} $request
     *
     * @return list<string>
     */
    private function requireNames(array $request): array
    {
        $names = $request['names'] ?? null;

        $this->assertNamesList($names);

        $result = [];

        foreach ($names as $name) {
            $result[] = $this->requireNameString($name);
        }

        return $result;
    }

    private function assertNamesList(mixed $names): void
    {
        if (\is_array($names) && [] !== $names) {
            return;
        }

        throw new \InvalidArgumentException(
            'Domain check request key "names" must be a non-empty list of strings.',
        );
    }

    private function requireNameString(mixed $name): string
    {
        if (!\is_string($name) || '' === \trim($name)) {
            throw new \InvalidArgumentException(
                'Domain check request key "names" must contain only non-empty strings.',
            );
        }

        return $name;
    }
}
