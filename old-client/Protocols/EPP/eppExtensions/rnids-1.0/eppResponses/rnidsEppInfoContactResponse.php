<?php
namespace EppRegistrar\EPP;

class rnidsEppInfoContactResponse extends eppInfoContactResponse {
    
	private function getContactExtensionPropertyValue($propertyName) {
		$xpath = $this->xPath();		
		$xpath->registerNamespace("rnids", "http://www.rnids.rs/epp/xml/contact-rnids-ext-1.0");
        $result = $xpath->query("/epp:epp/epp:response/epp:extension/rnids:contact-ext/rnids:$propertyName");
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
	}
	
    public function getContactIdent() {
        return $this->getContactExtensionPropertyValue("ident");
    }
	
	public function getContactIdentDescription() {
        return $this->getContactExtensionPropertyValue("identDescription");
    }
	
	public function getContactIdentKind() {
        return $this->getContactExtensionPropertyValue("identKind");
    }
	
	public function getContactIdentExpiry() {
        return $this->getContactExtensionPropertyValue("identExpiry");
    }
	
	public function getContactIsLegalEntity() {
        return $this->getContactExtensionPropertyValue("isLegalEntity");
    }
	
	public function getContactVatNo() {
        return $this->getContactExtensionPropertyValue("vatNo");
    }
}
