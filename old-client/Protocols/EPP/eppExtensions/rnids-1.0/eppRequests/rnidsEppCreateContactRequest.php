<?php
namespace EppRegistrar\EPP;

class rnidsEppCreateContactRequest extends eppCreateContactRequest {
    function __construct($createinfo, $ident='', $identDescription = '', $identExpiry = '', $isLegalEntity = '', $identKind = '', $vatNo = '') {

        if ($createinfo instanceof eppContact) {
            parent::__construct($createinfo);
            $this->addRnidsExtension($ident, $identDescription, $identExpiry, $isLegalEntity, $identKind, $vatNo);
        } else {
            throw new eppException('RNIDS does not support Contact objects');
        }
        $this->addSessionId();
    }


    public function addRnidsExtension($ident, $identDescription, $identExpiry, $isLegalEntity, $identKind, $vatNo) {
        $this->addExtension('xmlns:rnids', 'http://www.rnids.rs/epp/xml/contact-rnids-ext-1.0');
        $ext = $this->createElement('extension');
        $rnidsext = $this->createElement('rnids:contact-ext');
        $rnidsext->appendChild($this->createElement('rnids:ident', $ident));
		$rnidsext->appendChild($this->createElement('rnids:identDescription', $identDescription));
		$rnidsext->appendChild($this->createElement('rnids:identExpiry', $identExpiry));
		$rnidsext->appendChild($this->createElement('rnids:isLegalEntity', ($isLegalEntity ? '1' : '0')));
		$rnidsext->appendChild($this->createElement('rnids:identKind', $identKind));
		$rnidsext->appendChild($this->createElement('rnids:vatNo', $vatNo));
        $ext->appendChild($rnidsext);
        $this->command->appendChild($ext);
    }
}