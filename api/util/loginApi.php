<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

require 'util/decrypt.php';

// loginData has an encrypted json object that expects the following structure:
// { "username": "username", "password": "password" }
function loginApi(string $encodedLoginData): string {
    $loginData = json_decode(urlB64Decode($encodedLoginData), associative: true);

    $username = $loginData['username'];
    $password = $loginData['password'];

    $login = login($username, $password);

    if ($login[0] === true) {
        $response = new JsonResponse(
            RequestType::LOGIN,
            ResponseType::SUCCESS,
            $login[2]
        );
    } else {
        $response = new JsonResponse(
            RequestType::LOGIN,
            ResponseType::ERROR,
            $login[2]
        );
    }

    return $response->toJson();
}