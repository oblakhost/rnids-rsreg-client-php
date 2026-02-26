<?php
require('../autoloader.php');


$conn = new EppRegistrar\EPP\rnidsEppConnection();

// Connect to the EPP server
if ($conn->connect()) {
	greet($conn);
    if (login($conn)) {
        deletecontact($conn, '3a151c68-d224-454b-b487-50cb7195e1b3');
        logout($conn);
    }
}

function deletecontact($conn, $contactid) {
    try {
        $contact = new EppRegistrar\EPP\eppContactHandle($contactid);
		$request = new EppRegistrar\EPP\eppDeleteRequest($contact);
        if ((($response = $conn->writeandread($request)) instanceof EppRegistrar\EPP\eppDeleteResponse) && $response->Success()) {
			echo "Delete contact from server: " . $response->getResultMessage() . "\n";
			
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
    return null;
}