<?php
require('../autoloader.php');

$conn = new EppRegistrar\EPP\rnidsEppConnection();

if ($conn->connect()) {
	greet($conn);
    if (login($conn)) {
        createhost($conn, 'ns1.mynstest2.rs', '10.20.30.40');
        logout($conn);
    }
}

function createhost($conn, $hostname, $address) {
    try {
        $host = new EppRegistrar\EPP\eppHost($hostname, $address);
		$request = new EppRegistrar\EPP\eppCreateHostRequest($host);
        if ((($response = $conn->writeandread($request)) instanceof EppRegistrar\EPP\eppCreateResponse) && ($response->Success())) {
            echo "Host created on " . $response->getHostCreateDate() . " with name " . $response->getHostName() . "\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
    return null;
}