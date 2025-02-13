<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

require '../database/database.php';

function logoutApi(string $loginToken) : string {
    global $pdo;
    error_log("loginToken: $loginToken");

    list($isValid, $user) = verifyLoginToken($loginToken);

    if (!$isValid) {
        return (new JsonResponse(
            RequestType::LOGOUT,
            ResponseType::ERROR,
            'Error: Invalid login token.'
        ))->toJson();
    }

    $stmt = $pdo->prepare('UPDATE users SET login_token = NULL WHERE login_token = ?');
    $result = $stmt->execute([$user['login_token']]);

    if ($result) {
        return (new JsonResponse(
            RequestType::LOGOUT,
            ResponseType::SUCCESS,
            'Logout successful.'
        ))->toJson();
    }

    return (new JsonResponse(
        RequestType::LOGOUT,
        ResponseType::ERROR,
        'Error: Logout failed.'
    ))->toJson();
}