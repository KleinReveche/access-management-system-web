<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

require '../../database/database.php';
require '../models/JsonResponse.php';
require '../models/RequestType.php';
require '../models/ResponseType.php';

global $pdo;

$loginToken = $_GET['login_token'];

$stmt = $pdo->prepare('UPDATE users SET login_token = NULL WHERE login_token = ?');
$stmt->execute([$loginToken]);

header('Content-Type: application/json');
echo (new JsonResponse(
    RequestType::LOGOUT,
    ResponseType::SUCCESS,
    'Logout successful.'
))->toJson();