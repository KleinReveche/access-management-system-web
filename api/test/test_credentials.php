<?php

include '../util/encrypt.php';
include '../models/RequestType.php';

$loginData = json_encode([
    'username' => $_GET['username'],
    'password' => $_GET['password']
]);

header('Content-Type: txt/plain');
echo encrypt($loginData, "../../../");