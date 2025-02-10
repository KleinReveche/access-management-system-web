<?php

function decrypt(string $data, string $relativeDir = "../../"): string
{
    $privateKeyStr = file_get_contents($relativeDir . "private_key.pem");
    $encryptedData = urlB64Decode($data);

    openssl_private_decrypt($encryptedData, $decryptedData, $privateKeyStr);

    return $decryptedData;
}

function urlB64Decode($input): bool|string
{
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $pad_len = 4 - $remainder;
        $input .= str_repeat('=', $pad_len);
    }
    return base64_decode(strtr($input, '-_', '+/'));
}