<?php
require('../autoloader.php');

$conn = new EppRegistrar\EPP\rnidsEppConnection();

// Connect to the EPP server
if ($conn->connect()) {
	greet($conn);
    if (login($conn)) {
        deletehost($conn, 'ns2.mytestdomain2.rs');
        logout($conn);
    }
}

function deletehost($conn, $hostName) {
    try {
        $host = new EppRegistrar\EPP\eppHost($hostName);
		$request = new EppRegistrar\EPP\eppDeleteRequest($host);
        if ((($response = $conn->writeandread($request)) instanceof EppRegistrar\EPP\eppDeleteResponse) && ($response->Success())) {
            //print_r($response);
			echo "Delete host from server: " . $response->getResultMessage() . "\n";
            return;
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
    return null;
}