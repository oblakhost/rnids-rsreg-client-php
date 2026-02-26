<?php
require('../autoloader.php');

/*
 * This sample script registers a domain name within your account
 * 
 * The nameservers of EppRegistrar are used as nameservers
 * In this scrips, the same contact id is used for registrant, admin-contact, tech-contact and billing contact
 * Recommended usage is that you use a tech-contact and billing contact of your own, and set registrant and admin-contact to the domain name owner or reseller.
 */


if ($argc <= 1)
{
    echo "Usage: registerdomain.php <domainname>\n";
	echo "Please enter the domain name to be created\n\n";
	die();
}

$domainname = $argv[1];

echo "Registering $domainname started at " . date("Y-m-d H:i:s") . "\n";
$conn = new EppRegistrar\EPP\rnidsEppConnection();
// Connect to the EPP server
if ($conn->connect()) {
	greet($conn);
    if (login($conn)) {
		if (!checkhosts($conn, array('ns.test1.rs'))) {
            createhost($conn, 'ns.test1.rs');
        }
        if (!checkhosts($conn, array('ns.test2.rs'))) {
            createhost($conn, 'ns.test2.rs');
        }
        $nameservers = array('ns.test1.rs','ns.test2.rs');
        $contactid = createcontact($conn,'test@test.com','063123456','Person name','Organization','Address 1','12345','City','RS','12345', 'opis', date('Y-m-d'), true, 'personal_ID', 'vatNo123');
        if ($contactid) {
            createdomain($conn, $domainname, $contactid, $contactid, $contactid, $contactid, $nameservers);
        }
        logout($conn);
    }
	echo "Finished at " . date("Y-m-d H:i:s"); 
}


function checkcontact($conn, $contactid) {
    /* @var $conn EppRegistrar\EPP\eppConnection */
    try {
        $contactinfo = new EppRegistrar\EPP\eppContactHandle($contactid);
        $check = new EppRegistrar\EPP\eppCheckRequest($contactinfo);
        if ((($response = $conn->writeandread($check)) instanceof EppRegistrar\EPP\eppCheckResponse) && ($response->Success())) {
            /* @var $response EppRegistrar\EPP\eppCheckResponse */
            $checks = $response->getCheckedContacts();
            foreach ($checks as $contact => $check) {
                echo "Contact $contact " . ($check ? 'does not exist' : 'exists') . "\n";
            }
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
}


function createcontact($conn, $email, $telephone, $name, $organization, $address, $postcode, $city, $country, $ident, $identDescription, $identExpiry, $isLegalEntity, $identKind, $vatNo) {
    /* @var $conn EppRegistrar\EPP\eppConnection */
    try {
        $postalinfo = new EppRegistrar\EPP\eppContactPostalInfo($name, $city, $country, $organization, $address, null, $postcode, EppRegistrar\EPP\eppContact::TYPE_LOC);
        $contactinfo = new EppRegistrar\EPP\eppContact($postalinfo, $email, $telephone);
		$contact = new EppRegistrar\EPP\rnidsEppCreateContactRequest($contactinfo, $ident, $identDescription, $identExpiry, $isLegalEntity, $identKind, $vatNo);
        if ((($response = $conn->writeandread($contact)) instanceof EppRegistrar\EPP\eppCreateResponse) && ($response->Success())) {
            /* @var $response EppRegistrar\EPP\eppCreateResponse */
            echo "Contact created on " . $response->getContactCreateDate() . " with id " . $response->getContactId() . "\n";
            return $response->getContactId();
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
    return null;
}


function checkhosts($conn, $hosts) {
    /* @var $conn EppRegistrar\EPP\eppConnection */
    try {
        $checkhost = array();
        foreach ($hosts as $host) {
            $checkhost[] = new EppRegistrar\EPP\eppHost($host);
        }
        $check = new EppRegistrar\EPP\eppCheckRequest($checkhost);
        if ((($response = $conn->writeandread($check)) instanceof EppRegistrar\EPP\eppCheckResponse) && ($response->Success())) {
            /* @var $response EppRegistrar\EPP\eppCheckResponse */
            $checks = $response->getCheckedHosts();
            $allchecksok = true;
            foreach ($checks as $hostname => $check) {
                echo "$hostname " . ($check ? 'does not exist' : 'exists') . "\n";
                if ($check) {
                    $allchecksok = false;
                }
            }
            return $allchecksok;
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
    return null;
}


function createhost($conn, $hostname, $ipaddress=null) {
    /* @var $conn EppRegistrar\EPP\eppConnection */
    try {
        $create = new EppRegistrar\EPP\eppHost($hostname,$ipaddress);
        $host = new EppRegistrar\EPP\eppCreateHostRequest($create);
        if ((($response = $conn->writeandread($host)) instanceof EppRegistrar\EPP\eppCreateResponse) && ($response->Success())) {
            /* @var $response EppRegistrar\EPP\eppCreateResponse */
            echo "Host created on " . $response->getHostCreateDate() . " with name " . $response->getHostName() . "\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
}


function createdomain($conn, $domainname, $registrant, $admincontact, $techcontact, $billingcontact, $nameservers) {
    /* @var $conn EppRegistrar\EPP\eppConnection */
    try {
        $domain = new EppRegistrar\EPP\eppDomain($domainname, $registrant);
        $reg = new EppRegistrar\EPP\eppContactHandle($registrant);
        $domain->setRegistrant($reg);
        $admin = new EppRegistrar\EPP\eppContactHandle($admincontact, EppRegistrar\EPP\eppContactHandle::CONTACT_TYPE_ADMIN);
        $domain->addContact($admin);
        $tech = new EppRegistrar\EPP\eppContactHandle($techcontact, EppRegistrar\EPP\eppContactHandle::CONTACT_TYPE_TECH);
        $domain->addContact($tech);
        $billing = new EppRegistrar\EPP\eppContactHandle($billingcontact, EppRegistrar\EPP\eppContactHandle::CONTACT_TYPE_BILLING);
        $domain->addContact($billing);
        $domain->setAuthorisationCode('rand0m');
        if (is_array($nameservers))
        {
            foreach ($nameservers as $nameserver)
            {
                $host = new EppRegistrar\EPP\eppHost($nameserver);
                $domain->addHost($host);
            }
        }
		
		$remark='some remark'; $isWhoisPrivacy = false; $operationMode = 'normal'; $notifyAdmin = false; $dnsSec = false;
		
        $create = new EppRegistrar\EPP\rnidsEppCreateDomainRequest($domain, true, $remark, $isWhoisPrivacy, $operationMode, $notifyAdmin, $dnsSec);
        if ((($response = $conn->writeandread($create)) instanceof EppRegistrar\EPP\eppCreateResponse) && ($response->Success())) {
            /* @var $response EppRegistrar\EPP\eppCreateResponse */
            echo "Domain " . $response->getDomainName() . " created on " . $response->getDomainCreateDate() . ", expiration date is " . $response->getDomainExpirationDate() . "\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
}