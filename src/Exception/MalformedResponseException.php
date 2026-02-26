<?php

declare(strict_types=1);

namespace RNIDS\Exception;

/**
 * Raised when received XML cannot be parsed as a valid EPP response.
 */
final class MalformedResponseException extends \RNIDS\Exception\EppException
{
}
