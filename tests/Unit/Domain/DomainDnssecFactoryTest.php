<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RNIDS\Domain\DomainDnssecFactory;

#[Group('unit')]
final class DomainDnssecFactoryTest extends TestCase
{
    /** @return iterable<string, array{array{keyTag: int, alg: int, digestType: int, digest: string}}> */
    public static function invalidRecords(): iterable
    {
        $valid = [ 'keyTag' => 12345, 'alg' => 8, 'digestType' => 2, 'digest' => \str_repeat('AB', 32) ];
        yield 'key tag range' => [ \array_replace($valid, [ 'keyTag' => 65536 ]) ];
        yield 'unsupported algorithm' => [ \array_replace($valid, [ 'alg' => 255 ]) ];
        yield 'unsupported digest type' => [ \array_replace($valid, [ 'digestType' => 5 ]) ];
        yield 'digest length' => [ \array_replace($valid, [ 'digest' => 'AB' ]) ];
        yield 'digest hex' => [ \array_replace($valid, [ 'digest' => \str_repeat('XY', 32) ]) ];
    }

    /** @param array{keyTag: int, alg: int, digestType: int, digest: string} $record */
    #[DataProvider('invalidRecords')]
    public function testRejectsInvalidDsRecords(array $record): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DomainDnssecFactory())->create([ 'records' => [ $record ] ]);
    }

    public function testRemoveAllCannotBeCombinedWithIndividualRemovals(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $record = [ 'keyTag' => 12345, 'alg' => 8, 'digestType' => 2, 'digest' => \str_repeat('AB', 32) ];
        (new DomainDnssecFactory())->update([ 'remove' => [ $record ], 'removeAll' => true ]);
    }
}
