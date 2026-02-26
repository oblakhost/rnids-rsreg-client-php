<?php
require('../autoloader.php');

$conn = new EppRegistrar\EPP\rnidsEppConnection();

// Connect to the EPP server
if ($conn->connect()) {
	greet($conn);
    if (login($conn)) {
        infocontact($conn, 'c467ebe4-a0d5-4da2-8e75-c9af32a2f6cd');
        logout($conn);
    }

}


function infocontact($conn, $contactid) {
    try {
        $contact = new EppRegistrar\EPP\eppContactHandle($contactid);
		$request = new EppRegistrar\EPP\eppInfoContactRequest($contact);
        if ((($response = $conn->writeandread($request)) instanceof EppRegistrar\EPP\rnidsEppInfoContactResponse) && ($response->Success())) {
            echo "Contact from server: " . 
				$response->getContactId() . ","  . 
				$response->getContactRoId() . ","  . 
				$response->getContactName() . ","  . 
				$response->getContactVoice() . "," .
				$response->getContactIdent() . "," . 
				$response->getContactIdentDescription()  . "," . 
				$response->getContactIdentKind()  . "," . 
				$response->getContactIdentExpiry()  . "," . 
				$response->getContactIsLegalEntity()  . "," . 
				$response->getContactVatNo();
            return $response->getContactId();
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
    return null;
}