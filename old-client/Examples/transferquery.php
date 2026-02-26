<?php
require('../autoloader.php');

try {
    $conn = new EppRegistrar\EPP\rnidsEppConnection();
    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
            transferquery($conn, 'mytestdomain2.rs', '588ad499-97a0-44bd-a9b8-57677a597e5f');
            logout($conn);
        }
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

function transferquery($conn, $domainname, $authCode) {
    try {
        $domain = new EppRegistrar\EPP\eppDomain($domainname);
		$domain->setAuthorisationCode($authCode);
        $transfer = new EppRegistrar\EPP\eppTransferRequest(EppRegistrar\EPP\eppTransferRequest::OPERATION_QUERY,$domain);
        if ((($response = $conn->writeandread($transfer)) instanceof EppRegistrar\EPP\eppTransferResponse) && ($response->Success())) {
            echo $response->saveXML();
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
}