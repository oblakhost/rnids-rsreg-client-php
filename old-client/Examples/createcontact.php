<?php
require('../autoloader.php');

$conn = new EppRegistrar\EPP\rnidsEppConnection();

// Connect to the EPP server
if ($conn->connect()) {
	greet($conn);
    if (login($conn)) {
        createcontact($conn, 'test@test.rs', '+38.111232323', 'Test Test', 'Test d.o.o.', 'Test Address 11', '11000', 'Beograd', 'RS', '12345', 'opis', date('Y-m-d'), true, 'personal_ID', 'vatNo123');
        logout($conn);
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
        echo $e->getMessage() . "\n";
    }
    return null;
}