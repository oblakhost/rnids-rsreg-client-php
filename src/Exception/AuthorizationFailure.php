<?php

declare(strict_types=1);

namespace RNIDS\Exception;

/**
 * EPP command was denied due to missing permissions.
 */
final class AuthorizationFailure extends \RNIDS\Exception\ProtocolException
{
}
