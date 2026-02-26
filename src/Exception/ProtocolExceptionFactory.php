<?php

declare(strict_types=1);

namespace RNIDS\Exception;

use RNIDS\Xml\Response\EppResultCode;
use RNIDS\Xml\Response\ResponseMetadata;

final class ProtocolExceptionFactory
{
    public static function fromMetadata(ResponseMetadata $responseMetadata): \RNIDS\Exception\ProtocolException
    {
        return match ($responseMetadata->knownResultCode()) {
            EppResultCode::AuthenticationError => new \RNIDS\Exception\AuthenticationFailure(
                $responseMetadata,
            ),
            EppResultCode::AuthorizationError => new \RNIDS\Exception\AuthorizationFailure($responseMetadata),
            EppResultCode::InvalidAuthorizationInformation =>
                new \RNIDS\Exception\InvalidAuthorizationInformation($responseMetadata),
            EppResultCode::ObjectExists => new \RNIDS\Exception\ObjectAlreadyExists($responseMetadata),
            EppResultCode::ObjectDoesNotExist => new \RNIDS\Exception\ObjectMissing($responseMetadata),
            EppResultCode::ObjectStatusProhibitsOperation =>
                new \RNIDS\Exception\ObjectStatusConflict($responseMetadata),
            EppResultCode::ObjectAssociationProhibitsOperation =>
                new \RNIDS\Exception\ObjectAssociationConflict($responseMetadata),
            EppResultCode::ParameterValuePolicyError => new \RNIDS\Exception\PolicyViolation(
                $responseMetadata,
            ),
            default => new \RNIDS\Exception\ProtocolException($responseMetadata),
        };
    }
}
