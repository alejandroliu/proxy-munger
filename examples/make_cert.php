<?php
/**
 * Sample script used for creating server certificates.
 * 
 * @package proxy_munger
 */
// Optionally set the passphrase to whatever you want,
// or leave it empty for no passphrase
$pem_passphrase = 'comet';
// Output certificate file
$pemfile = './server.pem';
// Certificate data
$certificateData = [
    "countryName" => "NL",
    "stateOrProvinceName" => "ZH",
    "localityName" => "Den Haag",
    "organizationName" => "iliu.net",
    "organizationalUnitName" => "Development",
    "commonName" => "*.iliu.net",
    "emailAddress" => "alejandro_liu@hotmail.com"
];

// Generate certificate
$privateKey = openssl_pkey_new();
$certificate = openssl_csr_new($certificateData, $privateKey);
$certificate = openssl_csr_sign($certificate, null, $privateKey, 365);

// Generate PEM file
$pem = [];
openssl_x509_export($certificate, $pem[0]);
openssl_pkey_export($privateKey, $pem[1], $pem_passphrase);
$pem = implode($pem);

// Save PEM file
file_put_contents($pemfile, $pem);
