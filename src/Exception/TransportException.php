<?php

declare(strict_types=1);

namespace RNIDS\Exception;

/**
 * Signals transport-level failures such as socket and I/O errors.
 */
class TransportException extends \RNIDS\Exception\EppException
{
}
