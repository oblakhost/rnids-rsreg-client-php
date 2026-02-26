<?php
require('../autoloader.php');

/*
 * This script renews domain for a specified period and unit of time.
 */

try {
    $conn = new EppRegistrar\EPP\rnidsEppConnection();

    // Connect to the EPP server
    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
            renewdomain($conn, 'mytestdomain2.rs', "y", 3);
            logout($conn);
        }
    } else {
        echo "ERROR CONNECTING\n";
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}


function renewdomain($conn, $domainname, $unit, $period) {
    try {
        $domain = new EppRegistrar\EPP\eppDomain($domainname);
		$domain->setPeriodUnit($unit);
		$domain->setPeriod($period);
		
		$expiry = '2017-01-20';
		$info = new EppRegistrar\EPP\eppInfoDomainRequest(new EppRegistrar\EPP\eppDomain($domainname));
        if ((($response = $conn->writeandread($info)) instanceof EppRegistrar\EPP\eppInfoDomainResponse) && ($response->Success())) {
			$expiry = $response->getDomainExpirationDate();
			$expiry = date("Y-m-d", strtotime($expiry));
		}
		echo "Current domain expiry for $domainname is $expiry\n";
		
        $info = new EppRegistrar\EPP\eppRenewRequest($domain, $expiry);
        if ((($response = $conn->writeandread($info)) instanceof EppRegistrar\EPP\eppRenewResponse) && ($response->Success())) {
            echo "Domain name: " . $response->getDomainName() . "\n";
			echo "Expiry: " . $response->getDomainExpirationDate() . "\n";
        } else {
            echo "ERROR2\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo "ERROR1\n";
        echo $e->getMessage() . "\n";
    }
}