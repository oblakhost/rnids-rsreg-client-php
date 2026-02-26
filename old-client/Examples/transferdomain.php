<?php
require('../autoloader.php');

/*
 * This script requests a domain name transfer into your account
 */


if ($argc <= 1) {
    echo "Usage: transferdomain.php <domainname>\n";
    echo "Please provide the domain name for transfer\n\n";
    die();
}
$domainname = $argv[1];

echo "Transferring $domainname\n";
try {
    $conn = new EppRegistrar\EPP\rnidsEppConnection();
    // Connect and login to the EPP server
    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
            transferdomain($conn, $domainname);
            logout($conn);
        }
    } else {
        echo "ERROR CONNECTING\n";
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}


function transferdomain($conn, $domainname) {
    try {
        $domain = new EppRegistrar\EPP\eppDomain($domainname);
        $transfer = new EppRegistrar\EPP\eppTransferRequest(EppRegistrar\EPP\eppTransferRequest::OPERATION_REQUEST,$domain);
        if ((($response = $conn->writeandread($transfer)) instanceof EppRegistrar\EPP\eppTransferResponse) && ($response->Success())) {
            echo $response->getDomainName()," transfer request was succesful\n";
        } else {
            echo "ERROR2\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
}