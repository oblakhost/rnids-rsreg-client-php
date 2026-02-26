<?php
require('../autoloader.php');


try {
    $conn = new EppRegistrar\EPP\rnidsEppConnection();
    // Connect to the EPP server
    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
            echo "Creating contact\n";
            $contactid = createcontact($conn,'test123@test.com','063123456','Person name Old',null,'Address 1','12345','City','NL', '12345', 't1', date('Y-m-d'), false, 'other', 'vatNo123');
            echo "Updating $contactid\n";
            updatecontact($conn,$contactid, 'test2@test.com', '+38163123456', 'Person name New 2', 'Updated org', 'Updated address 1', '12345', 'City', 'RS', 'ident4', 'description', date('Y-m-d'), false, 'personal_ID', '48973476');
            logout($conn);
        }
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo $e->getMessage() . "\n";
    logout($conn);
}


function updatecontact($conn, $contactid, $email, $telephone, $name, $organization, $address, $postcode, $city, $country, $ident, $identDescription, $identExpiry, $isLegalEntity, $identKind, $vatNo) {
    try {
        $contact = new EppRegistrar\EPP\eppContactHandle($contactid);
        $update = new EppRegistrar\EPP\eppContact();
        $update->setVoice($telephone);
        $update->setEmail($email);
        $pi = new EppRegistrar\EPP\eppContactPostalInfo($name, $city, $country, $organization, $address, null, $postcode, EppRegistrar\EPP\eppContact::TYPE_LOC);
        $update->addPostalInfo($pi);
        $up = new EppRegistrar\EPP\rnidsEppUpdateContactRequest($contact, $update, $ident, $identDescription, $identExpiry, $isLegalEntity, $identKind, $vatNo);
        if ((($response = $conn->writeandread($up)) instanceof EppRegistrar\EPP\eppUpdateResponse) && ($response->Success())) {
            echo "Contact $contactid updated!\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
	   throw $e;
    }
}



function createcontact($conn, $email, $telephone, $name, $organization, $address, $postcode, $city, $country, $ident, $identDescription, $identExpiry, $isLegalEntity, $identKind, $vatNo) {
    try {
		$postalinfo = new EppRegistrar\EPP\eppContactPostalInfo($name, $city, $country, $organization, $address);
        $contactinfo = new EppRegistrar\EPP\eppContact($postalinfo, $email, $telephone);
        $contact = new EppRegistrar\EPP\rnidsEppCreateContactRequest($contactinfo, $ident, $identDescription, $identExpiry, $isLegalEntity, $identKind, $vatNo);
        if ((($response = $conn->writeandread($contact)) instanceof EppRegistrar\EPP\eppCreateResponse) && ($response->Success())) {
            echo "Contact created on " . $response->getContactCreateDate() . " with id " . $response->getContactId() . "\n";
            return $response->getContactId();
        }
    } catch (EppRegistrar\EPP\eppException $e) {
		throw $e;
    }
    return null;
}