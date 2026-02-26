<?php

declare(strict_types=1);

namespace RNIDS\Exception;

/**
 * EPP command is blocked by the current object status.
 */
final class ObjectStatusConflict extends \RNIDS\Exception\ProtocolException
{
}
