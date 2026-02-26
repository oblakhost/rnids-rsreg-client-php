<?php
require('../autoloader.php');

/*
 * This script checks for the availability of host names
 * You can specify multiple host names to be checked
 */


if ($argc <= 1) {
    echo "Usage: checkhost.php <hostnames>\n";
    echo "Please enter one or more host names to check\n\n";
    die();
}

for ($i = 1; $i < $argc; $i++) {
    $hosts[] = new EppRegistrar\EPP\eppHost($argv[$i]);
}

echo "Checking " . count($hosts) . " host names\n";
try {
    $conn = new EppRegistrar\EPP\rnidsEppConnection();
    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
            checkhosts($conn, $hosts);
            logout($conn);
        }
    } else {
        echo "ERROR CONNECTING\n";
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}


function checkhosts($conn, $hosts) {
    try {
        $check = new EppRegistrar\EPP\eppCheckRequest($hosts);
        if ((($response = $conn->writeandread($check)) instanceof EppRegistrar\EPP\eppCheckResponse) && ($response->Success())) {
            $checks = $response->getCheckedHosts();
			
            foreach ($checks as $host => $avail) {
                echo $host . " is " . ($avail == true ? 'free' : 'taken') . "\n";
            }
        } else {
            echo "ERROR2\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo 'ERROR1';
        echo $e->getMessage() . "\n";
    }
}