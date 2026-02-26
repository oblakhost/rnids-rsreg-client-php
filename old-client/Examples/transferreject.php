<?php
require('../autoloader.php');

try {
    $conn = new EppRegistrar\EPP\rnidsEppConnection();
    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
            transferreject($conn, 'mytestdomain2.rs', '80aeb39c-6b7a-47ef-9bb5-3322562bc7d0');
            logout($conn);
        }
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

function transferreject($conn, $domainname, $authCode) {
    try {
        $domain = new EppRegistrar\EPP\eppDomain($domainname);
		$domain->setAuthorisationCode($authCode);
        $transfer = new EppRegistrar\EPP\eppTransferRequest(EppRegistrar\EPP\eppTransferRequest::OPERATION_REJECT,$domain);
        if ((($response = $conn->writeandread($transfer)) instanceof EppRegistrar\EPP\eppTransferResponse) && ($response->Success())) {
            echo "Transfer of $domainname has been rejected\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
}