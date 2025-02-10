<?php

require '../database/database.php';

function login(string $username, string $password): array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['login_token'] != null) {
            return array(false, null, 'Error: User already logged in.');
        }

        $stmtLog = $pdo->prepare("INSERT INTO logs (admin_username, login_time, ip_address) VALUES (:username, NOW(), :ip_address)");
        $stmtLog->execute([
            ':username'   => $user['username'],
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);

        $loginToken = generateLoginToken();
        $stmtToken = $pdo->prepare("UPDATE users SET login_token = ? WHERE username = ?");
        $stmtToken->execute([$loginToken, $user['username']]);

        return array(true, $user, $loginToken);
    } else {
        return array(false, null, 'Error: Invalid username or password.');
    }
}

function generateLoginToken(): string
{
    return bin2hex(random_bytes(16));
}