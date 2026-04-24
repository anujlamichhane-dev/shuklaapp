<?php
require_once './src/security.php';
security_bootstrap_session();
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => security_is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
security_send_headers(true);
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

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
}
session_destroy();
header('Location: ./');
exit();
