<?php
namespace EppRegistrar\EPP;
#
# rfc5910
#
class eppInfoDomainResponse extends eppInfoResponse {


    /**
     *
     * @return eppDomain
     */
    public function getDomain() {
        $domainname = $this->getDomainName();
        $registrant = $this->getDomainRegistrant();
        $contacts = $this->getDomainContacts();
        $nameservers = $this->getDomainNameservers();
        $authinfo = $this->getDomainAuthInfo();
        $domain = new eppDomain($domainname, $registrant, $contacts, $nameservers, 1, $authinfo);
        return $domain;
    }

    /**
     *
     * @return string domainname
     */
    public function getDomainName() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:name');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     *
     * @return string status
     */
    public function getDomainStatuses() {
        $statuses = null;
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:status/@s');
        foreach ($result as $status) {
            $statuses[] = $status->nodeValue;
        }
        return $statuses;
    }

    /**
     *
     * @return string statuses
     */
    public function getDomainStatusCSV() {
        return parent::arrayToCSV($this->getDomainStatuses());
    }

    /**
     *
     * @return string roid
     */
    public function getDomainRoid() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:roid');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     *
     * @return string registrant id
     */
    public function getDomainRegistrant() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:registrant');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     *
     * @return string registrant id
     */
    public function getDomainContact($contacttype) {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:contact[@type=\'' . $contacttype . '\']');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     *
     * @return array eppContactHandles
     */
    public function getDomainContacts() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:contact');
        $cont = null;
        foreach ($result as $contact) {
            $contacttype = $contact->getAttribute('type');
            if ($contacttype) {
                // DNSBE specific, but too much hassle to create an override for this
                if ($contacttype == 'onsite') {
                    $contacttype = 'admin';
                }
                $cont[] = new eppContactHandle($contact->nodeValue, $contacttype);
            }
        }
        return $cont;
    }

    /**
     * This function returns the SUBORDINATE host objects of a domainname.
     * These must not be confused with the attached host objects.
     * Subordinate host objects are nameservers that end with the same string as the domain name.
     * They do not have to be connected to this domain name
     * @return array of eppHost
     */
    public function getDomainHosts() {
        $ns = null;
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:host');
        foreach ($result as $host) {
            $ns[] = new eppHost($host->nodeValue);
        }
        return $ns;
    }

    /**
     *
     * @return string create_date
     */
    public function getDomainCreateDate() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:crDate');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     *
     * @return string update_date
     */
    public function getDomainUpdateDate() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:upDate');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     *
     * @return string expiration_date
     */
    public function getDomainExpirationDate() {
        date_default_timezone_set("UTC");
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:exDate');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     *
     * @return string client id
     */
    public function getDomainClientId() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:clID');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     *
     * @return string client id
     */
    public function getDomainCreateClientId() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:crID');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     *
     * @return string client id
     */
    public function getDomainUpdateClientId() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:upID');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    /**
     * This function returns the associated nameservers from a domain object
     * Please do not confuse this with getDomainHosts(), which is used for subordinate host objects
     * @return array of strings
     */
    public function getDomainNameservers() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:ns/*');
        if ($result->length > 0) {
            $ns = null;
            foreach ($result as $nameserver) {
                if (strpos($nameserver->tagName, "hostObj") === 0) {
                    $ns[] = new eppHost(trim($nameserver->nodeValue));
                }
                if (strpos($nameserver->tagName, "hostAttr") === 0) {					
                    $hostname = $nameserver->getElementsByTagName('hostName')->item(0)->nodeValue;
                    $ipaddresses = $nameserver->getElementsByTagName('hostAddr');
                    $ips = null;
                    foreach ($ipaddresses as $ip) {
                        $ips[] = $ip->nodeValue;
                    }
                    $ns[] = new eppHost($hostname, $ips);
                }
            }
            return $ns;
        } else {
            return null;
        }
    }

    /**
     *
     * @return string nameservers
     */
    public function getDomainNameserversCSV() {
        $ns = $this->getDomainNameservers();
        foreach ($ns as $n) {
            $nameservers[] = $n->getHostname();
        }
        return parent::arrayToCSV($nameservers);
    }


    /**
     *
     * @return string authcode
     */
    public function getDomainAuthInfo() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:infData/domain:authInfo/domain:pw');
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    public function getKeydata() {
        // Check if dnssec is enabled on this interface
        if ($this->findNamespace('secDNS')) {
            $xpath = $this->xPath();
            $result = $xpath->query('/epp:epp/epp:response/epp:extension/secDNS:infData/*');
            $keys = array();
            if (count($result) > 0) {
                //foreach ($result as $keydata) {
                    $secdns = new eppSecdns();
                    $secdns->setFlags($result->item(0)->getElementsByTagName('flags')->item(0)->nodeValue);
                    $secdns->setAlgorithm($result->item(0)->getElementsByTagName('alg')->item(0)->nodeValue);
                    $secdns->setProtocol($result->item(0)->getElementsByTagName('protocol')->item(0)->nodeValue);
                    $secdns->setPubkey($result->item(0)->getElementsByTagName('pubKey')->item(0)->nodeValue);
                    $keys[] = $secdns;
                //}
            }
            return $keys;
        }
        return null;
    }

}