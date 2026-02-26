<?php
namespace EppRegistrar\EPP;

class rnidsEppInfoDomainResponse extends eppInfoDomainResponse {
    
	private function getDomainExtensionPropertyValue($propertyName) {
		$xpath = $this->xPath();		
		$xpath->registerNamespace("rnids", "http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0");
        $result = $xpath->query("/epp:epp/epp:response/epp:extension/rnids:domain-ext/rnids:$propertyName");
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
	}
	
    public function getWhoisPrivacy() {
        return $this->getDomainExtensionPropertyValue("isWhoisPrivacy");
    }
	
	public function getNotifyAdmin() {
        return $this->getDomainExtensionPropertyValue("notifyAdmin");
    }
	
	public function getDnsSec() {
        return $this->getDomainExtensionPropertyValue("dnsSec");
    }
	
	public function getRemark() {
        return $this->getDomainExtensionPropertyValue("remark");
    }
	
	public function getOperationMode() {
        return $this->getDomainExtensionPropertyValue("operationMode");
    }
}
