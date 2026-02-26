<?php
require('../autoloader.php');

try {
    echo "Polling for messages\n";
    $conn = new EppRegistrar\EPP\rnidsEppConnection();

    if ($conn->connect()) {
		greet($conn);
        if (login($conn)) {
            $messageid = poll($conn);
            if ($messageid) {
                pollack($conn, $messageid);
            }
            logout($conn);
        }
    }
} catch (EppRegistrar\EPP\eppException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}


function poll($conn) {
    try {
        $poll = new EppRegistrar\EPP\eppPollRequest(EppRegistrar\EPP\eppPollRequest::POLL_REQ, 0);
        if ((($response = $conn->writeandread($poll)) instanceof EppRegistrar\EPP\eppPollResponse) && ($response->Success())) {
            /* @var $response EppRegistrar\EPP\eppPollResponse */
            if ($response->getResultCode() == EppRegistrar\EPP\eppResponse::RESULT_MESSAGE_ACK) {
                echo $response->saveXML();
                echo $response->getMessageCount() . " messages waiting in the queue\n";
                echo "Picked up message " . $response->getMessageId() . ': ' . $response->getMessage() . "\n";
                return $response->getMessageId();
            } else {
                echo $response->getResultMessage() . "\n";
            }
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
    return null;
}

function pollack($conn, $messageid) {
    try {
        $poll = new EppRegistrar\EPP\eppPollRequest(EppRegistrar\EPP\eppPollRequest::POLL_ACK, $messageid);
        if ((($response = $conn->writeandread($poll)) instanceof EppRegistrar\EPP\eppPollResponse) && ($response->Success())) {
            echo "Message $messageid is acknowledged\n";
        }
    } catch (EppRegistrar\EPP\eppException $e) {
        echo $e->getMessage() . "\n";
    }
}