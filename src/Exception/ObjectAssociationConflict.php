<?php

declare(strict_types=1);

namespace RNIDS\Exception;

/**
 * EPP command is blocked by object association constraints.
 */
final class ObjectAssociationConflict extends \RNIDS\Exception\ProtocolException
{
}
