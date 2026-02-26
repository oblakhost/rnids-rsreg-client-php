<?php

declare(strict_types=1);

namespace RNIDS\Exception;

/**
 * EPP object creation failed because the object already exists.
 */
final class ObjectAlreadyExists extends \RNIDS\Exception\ProtocolException
{
}
