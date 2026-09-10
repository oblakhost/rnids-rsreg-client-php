<?php

declare(strict_types=1);

namespace RNIDS\Xml\Domain;

use RNIDS\Domain\Dto\DomainDsRecord;
use RNIDS\Exception\MalformedResponseException;

final class DomainDnssecResponseParser
{
    /** @return list<DomainDsRecord> */
    public function parse(\DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/epp:epp/epp:response/epp:extension/secDNS:infData/secDNS:dsData');
        $records = [];
        foreach ($nodes ?: [] as $node) {
            $records[] = $this->record($xpath, $node);
        }

        return $records;
    }

    private function record(\DOMXPath $xpath, \DOMNode $node): DomainDsRecord
    {
        try {
            return new DomainDsRecord(
                $this->number($xpath, $node, 'keyTag'),
                $this->number($xpath, $node, 'alg'),
                $this->number($xpath, $node, 'digestType'),
                \trim($xpath->evaluate('string(secDNS:digest)', $node)),
            );
        } catch (\InvalidArgumentException $exception) {
            throw new \RNIDS\Exception\MalformedResponseException(
                'Invalid DS record in domain info response.',
                0,
                $exception,
            );
        }
    }

    private function number(\DOMXPath $xpath, \DOMNode $node, string $field): int
    {
        $value = \trim($xpath->evaluate('string(secDNS:' . $field . ')', $node));
        if (1 !== \preg_match('/\A[0-9]+\z/', $value)) {
            throw new \RNIDS\Exception\MalformedResponseException(
                'Invalid DS record numeric field in domain info response.',
            );
        }

        return (int) $value;
    }
}
