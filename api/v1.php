<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

require 'util/getProducts.php';
require 'util/getProductCategories.php';
require 'util/getPublicKey.php';
require 'util/getRole.php';
require 'util/getVoucher.php';
require 'util/loginApi.php';
require 'util/logoutApi.php';
require '../util/login.php';
require_once 'models/RequestType.php';
require_once 'models/ResponseType.php';
require_once 'models/JsonResponse.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        process_request(
            $_GET['request'] ?? "",
            $_GET['data'] ?? ""
        );
        break;
    case 'POST':
        process_request(
            $_POST['request'] ?? "",
            $_POST['data'] ?? ""
        );
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method.'
        ]);
        exit;
}

function process_request(?string $request, ?string $data): void
{
    $loginToken = getallheaders()['Token'] ?? '';
    if (!empty($request) && !empty($data)) {
        echo match ($request) {
            RequestType::HELLO_WORLD->name => (new JsonResponse(
                RequestType::HELLO_WORLD,
                ResponseType::SUCCESS,
                'Hello, World!'
            ))->toJson(),
            RequestType::VOUCHER->name => getVoucher($data),
            RequestType::LOGIN->name => loginApi($data),
            RequestType::LOGOUT->name => logoutApi($data),
            RequestType::GET_PUBLIC_KEY->name => getPublicKey($loginToken),
            RequestType::GET_PRODUCTS->name => getProducts($loginToken),
            RequestType::GET_PRODUCT_CATEGORIES->name => getProductCategories($loginToken),
            RequestType::GET_ROLE->name => getRole($loginToken),
            default => (new JsonResponse(
                RequestType::OTHER,
                ResponseType::ERROR,
                'Invalid request.'
            ))->toJson()
        };
    } else {
        echo (new JsonResponse(
            RequestType::OTHER,
            ResponseType::ERROR,
            'Missing request or data parameter. | Request method: ' . $_SERVER['REQUEST_METHOD']  . ' | request: ' . $request . ' | data: ' . $data
        ))->toJson();
    }
}