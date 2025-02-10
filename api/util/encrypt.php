<?php

/**
 * Encrypts the given data using the public key
 *
 * @param string $data
 * @param string $relativeDir
 * @return string base64 url encoded encrypted data
 */
function encrypt(string $data, string $relativeDir = "../../"): string
{
    $publicKeyStr = file_get_contents($relativeDir . "public_key.pem");
    openssl_public_encrypt($data, $encryptedData, $publicKeyStr);
    return str_replace('=', '', strtr(base64_encode($encryptedData), '+/', '-_'));
}