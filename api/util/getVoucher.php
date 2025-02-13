<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

require_once '../vendor/autoload.php';

$acceptable_codes = [
    "8T64-2G3S",
    "MFMN-QODU",
    "IBZK-DDH9",
    "QJ3P-2VJL",
    "NTL7-VO8W"
];

function getVoucher(string $voucher): string
{
    global $acceptable_codes;

    error_log("Voucher code: $voucher");

    if (in_array($voucher, $acceptable_codes)) {
        return (new JsonResponse(
            RequestType::VOUCHER,
            ResponseType::SUCCESS,
            json_encode([
                "voucherCode" => generateVoucherCode(),
                "description" => "This includes a n% discount on all products."
            ])
        ))->toJson();
    } else {
        return (new JsonResponse(
            RequestType::VOUCHER,
            ResponseType::ERROR,
            "Invalid voucher code."
        ))->toJson();
    }
}


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