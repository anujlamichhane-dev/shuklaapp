<?php

function remember_cookie_secure() {
  if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) {
    return true;
  }
  return (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

function remember_cookie_domain() {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $host = preg_replace('/:\d+$/', '', $host);
  if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) || $host === 'localhost') {
    return '';
  }
  if (stripos($host, 'www.') === 0) {
    return '.' . substr($host, 4);
  }
  return '.' . $host;
}

function remember_set_cookie($token, $secure) {
  $domain = remember_cookie_domain();
  setcookie('remember_token', $token, [
    'expires' => time() + (60 * 60 * 24 * 30),
    'path' => '/',
    'domain' => $domain,
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

function remember_clear_cookie($secure) {
  $domain = remember_cookie_domain();
  setcookie('remember_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => $domain,
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

function remember_create_token($db, $userId, $secure) {
  $token = bin2hex(random_bytes(32));
  $hash = hash('sha256', $token);
  $expires = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 30));

  $delete = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
  if ($delete) {
    $delete->bind_param('i', $userId);
    $delete->execute();
    $delete->close();
  }

  $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
  if ($stmt === false) {
    return false;
  }

  $stmt->bind_param('iss', $userId, $hash, $expires);
  $ok = $stmt->execute();
  $stmt->close();

  if ($ok) {
    remember_set_cookie($token, $secure);
  }

  return $ok;
}

function remember_login($db, $secure) {
  if (empty($_COOKIE['remember_token'])) {
    return null;
  }

  $token = urldecode($_COOKIE['remember_token']);
  $token = strtolower(trim($token));
  if ($token === '' || strlen($token) < 20) {
    remember_clear_cookie($secure);
    return null;
  }
  if (!ctype_xdigit($token)) {
    remember_clear_cookie($secure);
    return null;
  }

  $hash = hash('sha256', $token);
  $now = date('Y-m-d H:i:s');

  $stmt = $db->prepare("SELECT user_id FROM remember_tokens WHERE token_hash = ? AND expires_at > ? LIMIT 1");
  if ($stmt === false) {
    return null;
  }

  $stmt->bind_param('ss', $hash, $now);
  if (!$stmt->execute()) {
    $stmt->close();
    return null;
  }

  $stmt->bind_result($userId);
  if (!$stmt->fetch()) {
    $stmt->close();
    remember_clear_cookie($secure);
    return null;
  }
  $stmt->close();

  $update = $db->prepare("UPDATE remember_tokens SET last_used_at = NOW() WHERE token_hash = ?");
  if ($update) {
    $update->bind_param('s', $hash);
    $update->execute();
    $update->close();
  }

  $userStmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
  if ($userStmt === false) {
    return null;
  }

  $userStmt->bind_param('i', $userId);
  if (!$userStmt->execute()) {
    $userStmt->close();
    return null;
  }

  $userStmt->bind_result($id, $name, $email, $role);
  if (!$userStmt->fetch()) {
    $userStmt->close();
    remember_clear_cookie($secure);
    return null;
  }
  $userStmt->close();

  $user = new stdClass();
  $user->id = $id;
  $user->name = $name;
  $user->email = $email;
  $user->role = $role;

  return $user;
}
