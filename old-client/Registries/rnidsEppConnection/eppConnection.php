<?php
namespace EppRegistrar\EPP;

class rnidsEppConnection extends eppConnection {

    public function __construct($logging = false, $settingsfile = null) {
        // Construct the EPP connection object en specify if you want logging on or off
        parent::__construct($logging, $settingsfile);
		//parent::enableCertification('CLIENT_CERTIFICATE_PATH', null, 'CA_ROOT_CERTIFICATE_PATH', '*.rnids.rs');
		
		//parent::setPort(3121); //Port koji ne koristi secure vezu - ako je aktivan ovaj port, ne treba pozivati enableCertification metodu.
		
        // Default server configuration stuff - this varies per connected registry
        parent::addExtension('domain-rnids-ext', 'http://www.rnids.rs/epp/xml/rnids-1.0');
		parent::addExtension('contact-rnids-ext', 'http://www.rnids.rs/epp/xml/rnids-1.0');
		parent::addCommandResponse('EppRegistrar\EPP\rnidsEppCreateContactRequest', 'EppRegistrar\EPP\eppCreateResponse'); 
		parent::addCommandResponse('EppRegistrar\EPP\eppInfoContactRequest', 'EppRegistrar\EPP\rnidsEppInfoContactResponse');
		parent::addCommandResponse('EppRegistrar\EPP\rnidsEppUpdateContactRequest', 'EppRegistrar\EPP\eppUpdateContactResponse');
    }

    public function setParams($params) {
        foreach ($params as $k => $param) {
            call_user_func(array($this, 'set' . ucfirst($k)), $param);
        }
    }
}
