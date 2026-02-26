<?php
require('../autoloader.php');
/*
 * This sample script modifies a domain name within your account
 * 
 * The nameservers of EppRegistrar are used as nameservers
 * In this scrips, the same contact id is used for registrant, admin-contact, tech-contact and billing contact
 * Recommended usage is that you use a tech-contact and billing contact of your own, and set registrant and admin-contact to the domain name owner or reseller.
 */


if ($argc <= 1) {
    echo "Usage: modifydomain.php <domainname>\n";
    echo "Please enter the domain name to be modified\n\n";
    die();
}

$domainname = $argv[1];

echo "Modifying $domainname\n";
try {
    $conn = new EppRegistrar\EPP\rnidsEppConnection();
    // Connect to the EPP server
    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
			//$ns = array('ns1.test1.rs', 'ns0.test.com', 'ns0ne1.example.com');
            //modifydomain($conn, $domainname, null, null, null, null, $ns, null);
            modifydomain($conn, $domainname, null, null, null, null, null, null);
            logout($conn);
        }
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo $e->getMessage() . "\n";
    logout($conn);
}


function modifydomain($conn, $domainname, $registrant = null, $admincontact = "", $techcontact = null, $billingcontact = null, $nameservers = null, $statuses = null) {
    try {
        $domain = new EppRegistrar\EPP\eppDomain($domainname);
        // First, retrieve the current domain info. Nameservers can be unset and then set again.
        $remark=null; 
		$isWhoisPrivacy = false;
		$operationMode = 'normal';
		$notifyAdmin = false;
		$dnsSec = false;
		
		$del = null;
        $info = new EppRegistrar\EPP\eppInfoDomainRequest($domain);
        if ((($response = $conn->writeandread($info)) instanceof EppRegistrar\EPP\rnidsEppInfoDomainResponse) && ($response->Success())) {
            // If new nameservers are given, get the old ones to remove them
            if (is_array($nameservers)) {
                $oldns = $response->getDomainNameservers();
                if (is_array($oldns)) {
                    if (!$del) {
                        $del = new EppRegistrar\EPP\eppDomain($domainname);
                    }
                    foreach ($oldns as $ns) {
                        $del->addHost($ns);
                    }
                }
            }
			
			if (is_array($statuses)) {
				$oldStatuses = $response->getDomainStatuses();
				if (is_array($oldStatuses)) {
                    if (!$del) {
                        $del = new EppRegistrar\EPP\eppDomain($domainname);
                    }
                    foreach ($oldStatuses as $stat) {
						echo "$stat has client position at " . strpos($stat, 'client') . "\n";
						if (strpos($stat, 'client') === 0) {
							echo "dodao: $stat";
							$del->addStatus($stat);
						}
                    }
                }
			}
			//Set all existing details from extensions:
			$isWhoisPrivacy = $response->getWhoisPrivacy();
			$operationMode = $response->getOperationMode();
			$notifyAdmin = $response->getNotifyAdmin();
			$dnsSec = $response->getDnsSec();
			
        }
        // In the UpdateDomain command you can set or add parameters
        // - Registrant is always set (you can only have one registrant)
        // - Admin, Tech contacts are Added (you can have multiple contacts, don't forget to remove the old ones)
        // - Nameservers are Added (you can have multiple nameservers, don't forget to remove the old ones)
        $mod = null;
        if ($registrant) {
            $mod = new EppRegistrar\EPP\eppDomain($domainname);
            $reg = new EppRegistrar\EPP\eppContactHandle($registrant);
            $mod->setRegistrant($reg);
        }
        $add = null;
        if ($admincontact) {
            if (!$add) {
                $add = new EppRegistrar\EPP\eppDomain($domainname);
            }
            $admin = new EppRegistrar\EPP\eppContactHandle($admincontact, EppRegistrar\EPP\eppContactHandle::CONTACT_TYPE_ADMIN);
            $add->addContact($admin);
        }
        if ($techcontact) {
            if (!$add) {
                $add = new EppRegistrar\EPP\eppDomain($domainname);
            }
            $tech = new EppRegistrar\EPP\eppContactHandle($techcontact, EppRegistrar\EPP\eppContactHandle::CONTACT_TYPE_TECH);
            $add->addContact($tech);
        }
        
        if (is_array($nameservers)) {
            if (!$add) {
                $add = new EppRegistrar\EPP\eppDomain($domainname);
            }
            foreach ($nameservers as $nameserver) {
                $host = new EppRegistrar\EPP\eppHost($nameserver);
                $add->addHost($host);
            }
        }
        
		//set $isWhoisPrivacy to true
		//$isWhoisPrivacy = true;
        $update = new EppRegistrar\EPP\rnidsEppUpdateDomainRequest($domain, $add, $del, $mod, false, $remark, $isWhoisPrivacy, $operationMode, $notifyAdmin, $dnsSec);
        if ((($response = $conn->writeandread($update)) instanceof EppRegistrar\EPP\eppUpdateResponse) && ($response->Success())) {
            echo $response->getResultMessage() . "\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
        if ($response instanceof EppRegistrar\EPP\eppUpdateResponse) {
            echo $response->textContent . "\n";
        }
    }
}