<?php

declare(strict_types=1);

namespace RNIDS\Domain;

use RNIDS\Domain\Dto\DomainDnssecCreate;
use RNIDS\Domain\Dto\DomainDnssecUpdate;
use RNIDS\Domain\Dto\DomainDsRecord;

/** Validates the RNIDS-supported subset of RFC 5910. */
final class DomainDnssecFactory
{
    /** @param array{records?: list<array{keyTag: int, alg: int, digestType: int, digest: string}>}|null $input */
    public function create(?array $input): ?DomainDnssecCreate
    {
        if (null === $input) {
            return null;
        }

        $this->assertKeys($input, [ 'records' ]);

        return new DomainDnssecCreate($this->records($input['records'] ?? []));
    }

    /** @param array{add?: list<array{keyTag: int, alg: int, digestType: int, digest: string}>, remove?: list<array{keyTag: int, alg: int, digestType: int, digest: string}>, removeAll?: bool}|null $input */
    public function update(?array $input): ?DomainDnssecUpdate
    {
        if (null === $input) {
            return null;
        }

        $this->assertKeys($input, [ 'add', 'remove', 'removeAll' ]);
        $removeAll = $input['removeAll'] ?? false;
        if (!\is_bool($removeAll)) {
            throw new \InvalidArgumentException('DNSSEC removeAll must be a boolean.');
        }

        return new DomainDnssecUpdate(
            $this->records($input['add'] ?? []),
            $this->records($input['remove'] ?? []),
            $removeAll,
        );
    }

    /** @return list<DomainDsRecord> */
    private function records(mixed $records): array
    {
        if (!\is_array($records) || !\array_is_list($records)) {
            throw new \InvalidArgumentException('DNSSEC records must be a list.');
        }

        return \array_map(fn(mixed $record): DomainDsRecord => $this->record($record), $records);
    }

    private function record(mixed $record): DomainDsRecord
    {
        if (!\is_array($record)) {
            throw new \InvalidArgumentException('A DNSSEC DS record must be an array.');
        }
        $this->assertKeys($record, [ 'keyTag', 'alg', 'digestType', 'digest' ]);
        $this->assertNumericFields($record);
        if (!isset($record['digest']) || !\is_string($record['digest'])) {
            throw new \InvalidArgumentException('DS record digest must be a hexadecimal string.');
        }

        return new DomainDsRecord(
            $record['keyTag'],
            $record['alg'],
            $record['digestType'],
            $record['digest'],
        );
    }

    /** @param array{keyTag?: mixed, alg?: mixed, digestType?: mixed, digest?: mixed} $record */
    private function assertNumericFields(array $record): void
    {
        foreach ([ 'keyTag', 'alg', 'digestType' ] as $field) {
            if (!isset($record[$field]) || !\is_int($record[$field])) {
                throw new \InvalidArgumentException('DS record numeric fields must be integers.');
            }
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param list<string> $allowed
     */
    private function assertKeys(array $input, array $allowed): void
    {
        if ([] !== \array_diff(\array_keys($input), $allowed)) {
            throw new \InvalidArgumentException('Unsupported RNIDS DNSSEC field.');
        }
    }
}
