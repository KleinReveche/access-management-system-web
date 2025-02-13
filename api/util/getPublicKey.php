<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

require 'models/JsonResponse.php';
require 'models/RequestType.php';
require 'models/ResponseType.php';
require '../util/verifyLoginToken.php';

function getPublicKey(string $loginToken): string
{
    $login = verifyLoginToken($loginToken);

    if ($login[0] === true) {
        return (new JsonResponse(
            RequestType::GET_PUBLIC_KEY,
            ResponseType::SUCCESS,
            file_get_contents('../../public_key.pem')
        ))->toJson();
    } else {
        return (new JsonResponse(
            RequestType::GET_PUBLIC_KEY,
            ResponseType::ERROR,
            "Invalid login token."
        ))->toJson();
    }
}