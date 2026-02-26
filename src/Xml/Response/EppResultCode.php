<?php

declare(strict_types=1);

namespace RNIDS\Xml\Response;

enum EppResultCode: int
{
    case CommandCompletedSuccessfully = 1000;
    case CommandCompletedSuccessfullyActionPending = 1001;
    case CommandCompletedSuccessfullyNoMessages = 1300;
    case CommandCompletedSuccessfullyAckToDequeue = 1301;
    case CommandCompletedSuccessfullyEndingSession = 1500;

    case UnknownCommand = 2000;
    case CommandSyntaxError = 2001;
    case CommandUseError = 2002;
    case RequiredParameterMissing = 2003;
    case ParameterValueRangeError = 2004;
    case ParameterValueSyntaxError = 2005;

    case UnimplementedProtocolVersion = 2100;
    case UnimplementedCommand = 2101;
    case UnimplementedOption = 2102;
    case UnimplementedExtension = 2103;
    case BillingFailure = 2104;
    case ObjectNotEligibleForRenewal = 2105;
    case ObjectNotEligibleForTransfer = 2106;

    case AuthenticationError = 2200;
    case AuthorizationError = 2201;
    case InvalidAuthorizationInformation = 2202;

    case ObjectPendingTransfer = 2300;
    case ObjectNotPendingTransfer = 2301;
    case ObjectExists = 2302;
    case ObjectDoesNotExist = 2303;
    case ObjectStatusProhibitsOperation = 2304;
    case ObjectAssociationProhibitsOperation = 2305;
    case ParameterValuePolicyError = 2306;
    case UnimplementedObjectService = 2307;
    case DataManagementPolicyViolation = 2308;

    case CommandFailed = 2400;

    case CommandFailedEndingSession = 2500;
    case AuthenticationErrorEndingSession = 2501;
    case SessionLimitExceededEndingSession = 2502;
}
