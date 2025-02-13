<?php

require_once '../vendor/autoload.php';

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Common\EccLevel;

function generateVoucherCode(): string
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $voucherCode = '';

    for ($i = 0; $i < 12; $i++) {
        if ($i > 0 && $i % 4 == 0) {
            $voucherCode .= '-';
        }
        $voucherCode .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $voucherCode;
}

/**
 * Generate QR Code in SVG format
 *
 * @param string $link
 * @return string
 */
function generateQRCode(string $link) : string
{
    $options = new QROptions([
        'version'         => 7,
        'eccLevel'        => EccLevel::H,
        'outputBase64'    => false,
        'scale'           => 30
    ]);

    return (new QRCode($options))->render($link);
}

$voucherCode = generateVoucherCode();

$link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']
    === 'on' ? "https" : "http") .
    "://" . $_SERVER['HTTP_HOST'] .
    "/api/voucher.php?voucher=" . $voucherCode;


echo "Voucher Code: " . $voucherCode . "<br>";
echo "Note: Voucher code here is not a valid voucher code.<br><br>";
echo generateQRCode($link);