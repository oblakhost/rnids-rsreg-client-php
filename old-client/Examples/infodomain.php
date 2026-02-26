<?php
require('../autoloader.php');

/*
 * This script checks for the availability of domain names
 *
 * You can specify multiple domain names to be checked
 */


if ($argc <= 1) {
    echo "Usage: infodomain.php <domainname>\n";
    echo "Please enter a domain name retrieve\n\n";
    die();
}

$domainname = $argv[1];

echo "Retrieving info on " . $domainname . "\n";
try {
    $conn = new EppRegistrar\EPP\rnidsEppConnection();

    // Connect to the EPP server
    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
            infodomain($conn, $domainname);
            logout($conn);
        }
    } else {
        echo "ERROR CONNECTING\n";
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}


function infodomain($conn, $domainname) {
    try {
        $epp = new EppRegistrar\EPP\eppDomain($domainname);
        $info = new EppRegistrar\EPP\eppInfoDomainRequest($epp);
        if ((($response = $conn->writeandread($info)) instanceof EppRegistrar\EPP\rnidsEppInfoDomainResponse) && ($response->Success())) {
            
            $d = $response->getDomain();
            echo "Info domain for " . $d->getDomainname() . ":\n";
            echo "Created on " . $response->getDomainCreateDate() . "\n";            
			echo "Last update on ".$response->getDomainUpdateDate()."\n";
			
            echo "Whois privacy ".$response->getWhoisPrivacy()."\n";
            echo "Operation mode ".$response->getOperationMode()."\n";
			
			echo "Registrant " . $d->getRegistrant() . "\n";
            echo "Contact info:\n";
            foreach ($d->getContacts() as $contact) {
                echo "  " . $contact->getContactType() . ": " . $contact->getContactHandle() . "\n";
            }
            echo "Nameserver info:\n";
            foreach ($d->getHosts() as $nameserver) {
                echo "  " . $nameserver->getHostName() . "\n";
            }

        } else {
            echo "ERROR2\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo "ERROR1\n";
        echo $e->getMessage() . "\n";
    }
}