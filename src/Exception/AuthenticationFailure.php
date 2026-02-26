<?php

declare(strict_types=1);

namespace RNIDS\Exception;

/**
 * EPP authentication failed due to invalid credentials or login state.
 */
final class AuthenticationFailure extends \RNIDS\Exception\ProtocolException
{
}
