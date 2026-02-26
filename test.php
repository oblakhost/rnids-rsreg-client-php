<?php

require_once __DIR__ . '/vendor/autoload.php';

$c = new RNIDS\SyncEppClient(
    hostname: 'epp-test.rnids.rs',
    port: 700,
    localCert: __DIR__ . '/client-cert.pem',
    localCertPwd: '12345',
    caFile: __DIR__ . '/ca-cert.pem',
    allowSelfSigned: true,
);

$c->connect();
