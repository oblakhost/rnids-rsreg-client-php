<?php
require('../autoloader.php');

$conn = new EppRegistrar\EPP\rnidsEppConnection();

// Connect to the EPP server
if ($conn->connect()) {
	greet($conn);
    if (login($conn)) {
        deletedomain($conn, 'myexistingdomain-1017581533737350200.rs');
        logout($conn);
    }
}

function deletedomain($conn, $domainName) {
    try {
        $domain = new EppRegistrar\EPP\eppDomain($domainName);
		$request = new EppRegistrar\EPP\eppDeleteRequest($domain);
        if ((($response = $conn->writeandread($request)) instanceof EppRegistrar\EPP\eppDeleteResponse) && ($response->Success())) {
			//var_dump($response->getResultMessage());
            echo "Delete domain from server: " . $response->getResultMessage() . "\n";
			return;
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
    return null;
}