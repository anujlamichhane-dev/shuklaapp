<?php
session_start();
require_once './src/Database.php';
require_once './src/remember.php';

$secure = remember_cookie_secure();
if (!empty($_COOKIE['remember_token'])) {
    $hash = hash('sha256', $_COOKIE['remember_token']);
    $db = Database::getInstance();
    $stmt = $db->prepare("DELETE FROM remember_tokens WHERE token_hash = ?");
    if ($stmt) {
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $stmt->close();
    }
    remember_clear_cookie($secure);
}

session_destroy();
header('Location: ./index.php');
exit();
