<?php
require('../autoloader.php');

/*
 * This script checks for the availability of host names
 *
 * You can specify multiple host names to be checked
 */


if ($argc <= 1) {
    echo "Usage: infohost.php <hostname>\n";
    echo "Please enter a host name retrieve\n\n";
    die();
}

$hostname = $argv[1];

echo "Retrieving info on " . $hostname . "\n";
try {
    $conn = new EppRegistrar\EPP\rnidsEppConnection();

    // Connect to the EPP server
    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
            infohost($conn, $hostname);
            logout($conn);
        }
    } else {
        echo "ERROR CONNECTING\n";
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}


function infohost($conn, $hostname) {
    try {
        $epp = new EppRegistrar\EPP\eppHost($hostname);
        $info = new EppRegistrar\EPP\eppInfoHostRequest($epp);
        if ((($response = $conn->writeandread($info)) instanceof EppRegistrar\EPP\eppInfoHostResponse) && ($response->Success())) {
			$host = $response->getHost();
			echo "Host from server: \n";
			echo "Name: " . $host->getHostname() . "\n";
			foreach($host->getIpAddresses() as $ip => $type) {
				echo "Ip:$ip of type:$type\n";
			}
        } else {
            echo "ERROR2\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo "ERROR1\n";
        echo $e->getMessage() . "\n";
    }
}