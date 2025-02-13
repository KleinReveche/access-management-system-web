<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

function getRole(string $loginToken): string
{
    $login = verifyLoginToken($loginToken);

    if ($login[0] === true) {
        return (new JsonResponse(
            RequestType::GET_ROLE,
            ResponseType::SUCCESS,
            $login[1]['role']
        ))->toJson();
    } else {
        return (new JsonResponse(
            RequestType::GET_ROLE,
            ResponseType::ERROR,
            "Invalid login token."
        ))->toJson();
    }
}