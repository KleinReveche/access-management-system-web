<?php

require '../database/database.php';

function verifyLoginToken(string $loginToken): array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE login_token = ?');
    $stmt->execute([$loginToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return array(true, $user);
    } else {
        return array(false, null);
    }
}