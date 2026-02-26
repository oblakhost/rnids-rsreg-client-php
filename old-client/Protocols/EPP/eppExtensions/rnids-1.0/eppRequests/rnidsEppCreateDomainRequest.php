<?php
namespace EppRegistrar\EPP;
/*
<extension>
	<rnids:domain-ext>
		<rnids:remark>some remark</rnids:remark>
		<rnids:isWhoisPrivacy>true</rnids:isWhoisPrivacy>
		<rnids:operationMode>secure</rnids:operationMode>
		<rnids:notifyAdmin>false</rnids:notifyAdmin>
		<rnids:dnsSec>true</rnids:dnsSec>
	</rnids:domain-ext>
</extension>
*/

class rnidsEppCreateDomainRequest extends eppCreateDomainRequest {
    function __construct($createinfo, $forcehostattr = false, $remark='', $isWhoisPrivacy = false, $operationMode = 'normal', $notifyAdmin = false, $dnsSec = false) {

        if ($createinfo instanceof eppDomain) {
            parent::__construct($createinfo, $forcehostattr);
            $this->addRnidsExtension($createinfo, $remark, $isWhoisPrivacy, $operationMode, $notifyAdmin, $dnsSec);
        } else {
            throw new eppException('Rnids does not support Host objects');
        }
        $this->addSessionId();
    }


    public function addRnidsExtension(eppDomain $domain, $remark, $isWhoisPrivacy, $operationMode, $notifyAdmin, $dnsSec) {
        $this->addExtension('xmlns:rnids', 'http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0');
        $ext = $this->createElement('extension');
        $rnidsext = $this->createElement('rnids:domain-ext');
        $rnidsext->appendChild($this->createElement('rnids:remark', $remark));
		$rnidsext->appendChild($this->createElement('rnids:isWhoisPrivacy', ($isWhoisPrivacy ? 'true' : 'false')));
		$rnidsext->appendChild($this->createElement('rnids:operationMode', $operationMode));
		$rnidsext->appendChild($this->createElement('rnids:notifyAdmin', ($notifyAdmin ? 'true' : 'false')));
		$rnidsext->appendChild($this->createElement('rnids:dnsSec', ($dnsSec ? 'true' : 'false')));
        $ext->appendChild($rnidsext);
        $this->command->appendChild($ext);
    }
}