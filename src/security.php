<?php

function security_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
}

function security_bootstrap_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');
}

function security_send_headers(bool $noStore = false): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('X-Permitted-Cross-Domain-Policies: none');

    if ($noStore) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    if (security_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('CSRF token requires an active session.');
    }

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_validate_request(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    $submitted = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($submitted)
        && is_string($sessionToken)
        && $submitted !== ''
        && hash_equals($sessionToken, $submitted);
}

function csrf_require_valid_request(): void
{
    if (!csrf_validate_request()) {
        http_response_code(419);
        exit('Security validation failed. Please refresh the page and try again.');
    }
}

function security_client_ip(): string
{
    return trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown')) ?: 'unknown';
}

function security_rate_limit_dir(): string
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'security-rate-limits';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function security_rate_limit_file(string $scope, string $identity): string
{
    return security_rate_limit_dir() . DIRECTORY_SEPARATOR . hash('sha256', $scope . '|' . $identity) . '.json';
}

function security_rate_limit_check(string $scope, string $identity, int $maxAttempts, int $windowSeconds): array
{
    $file = security_rate_limit_file($scope, $identity);
    $now = time();
    $data = ['attempts' => [], 'blocked_until' => 0];

    if (is_file($file)) {
        $decoded = json_decode((string)file_get_contents($file), true);
        if (is_array($decoded)) {
            $data = array_merge($data, $decoded);
        }
    }

    $attempts = [];
    foreach (($data['attempts'] ?? []) as $ts) {
        $ts = (int)$ts;
        if ($ts > ($now - $windowSeconds)) {
            $attempts[] = $ts;
        }
    }

    $blockedUntil = (int)($data['blocked_until'] ?? 0);
    $isBlocked = $blockedUntil > $now;

    return [
        'allowed' => !$isBlocked && count($attempts) < $maxAttempts,
        'retry_after' => $isBlocked ? max(1, $blockedUntil - $now) : 0,
        'attempts' => $attempts,
        'file' => $file,
    ];
}

function security_rate_limit_hit(string $scope, string $identity, int $maxAttempts, int $windowSeconds, int $blockSeconds): array
{
    $status = security_rate_limit_check($scope, $identity, $maxAttempts, $windowSeconds);
    $now = time();
    $attempts = $status['attempts'];
    $attempts[] = $now;

    $payload = [
        'attempts' => $attempts,
        'blocked_until' => count($attempts) >= $maxAttempts ? ($now + $blockSeconds) : 0,
    ];

    @file_put_contents($status['file'], json_encode($payload));

    return [
        'blocked' => $payload['blocked_until'] > $now,
        'retry_after' => $payload['blocked_until'] > $now ? ($payload['blocked_until'] - $now) : 0,
    ];
}

function security_rate_limit_reset(string $scope, string $identity): void
{
    $file = security_rate_limit_file($scope, $identity);
    if (is_file($file)) {
        @unlink($file);
    }
}

function security_allowed_upload_mimes(): array
{
    return [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'csv' => ['text/plain', 'text/csv', 'application/vnd.ms-excel'],
        'txt' => ['text/plain'],
    ];
}

function security_detect_mime(string $tmpName): string
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            if ($mime !== '') {
                return $mime;
            }
        }
    }

    return (string)(mime_content_type($tmpName) ?: '');
}

function security_validate_upload(string $tmpName, string $originalName, int $size, array $allowedExt, int $maxBytes): array
{
    if ($size > $maxBytes) {
        return ['ok' => false, 'message' => 'File is too large.'];
    }

    if (!is_uploaded_file($tmpName)) {
        return ['ok' => false, 'message' => 'Invalid upload.'];
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'message' => 'Unsupported file type.'];
    }

    $mime = security_detect_mime($tmpName);
    $mimeMap = security_allowed_upload_mimes();
    $allowedMimes = $mimeMap[$ext] ?? [];
    if ($mime === '' || (!empty($allowedMimes) && !in_array($mime, $allowedMimes, true))) {
        return ['ok' => false, 'message' => 'Uploaded file content does not match the file type.'];
    }

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) && @getimagesize($tmpName) === false) {
        return ['ok' => false, 'message' => 'Invalid image upload.'];
    }

    return ['ok' => true, 'mime' => $mime, 'ext' => $ext];
}
