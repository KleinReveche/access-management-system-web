<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

require 'util/encrypt.php';

function getRole(string $loginToken): string
{
    $login = verifyLoginToken($loginToken);

    if ($login[0] === true) {
        return (new JsonResponse(
            RequestType::GET_ROLE,
            ResponseType::SUCCESS,
            encrypt($login[1]['role'])
        ))->toJson();
    } else {
        return (new JsonResponse(
            RequestType::GET_ROLE,
            ResponseType::ERROR,
            "Invalid login token."
        ))->toJson();
    }
}