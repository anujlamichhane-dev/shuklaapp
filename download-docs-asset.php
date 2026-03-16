<?php
session_start();

if (!isset($_SESSION['logged-in']) || $_SESSION['logged-in'] == false) {
    http_response_code(403);
    exit('Not authorized.');
}

$relativePath = isset($_GET['path']) ? (string)$_GET['path'] : '';
$relativePath = str_replace('\\', '/', $relativePath);
$relativePath = ltrim($relativePath, '/');
$inline = isset($_GET['inline']) && $_GET['inline'] === '1';

if ($relativePath === '') {
    http_response_code(400);
    exit('Invalid request.');
}

$baseDir = realpath(__DIR__ . '/data/documents-info-uploads');
$target = realpath(__DIR__ . '/' . $relativePath);

if (!$baseDir || !$target || strpos($target, $baseDir) !== 0 || !is_file($target)) {
    http_response_code(404);
    exit('File not found.');
}

$mime = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = (string)finfo_file($finfo, $target);
        if ($detected !== '') {
            $mime = $detected;
        }
        finfo_close($finfo);
    }
}

if ($mime === 'application/octet-stream') {
    $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg'], true)) {
        $mime = 'image/jpeg';
    } elseif ($ext === 'png') {
        $mime = 'image/png';
    } elseif ($ext === 'gif') {
        $mime = 'image/gif';
    } elseif ($ext === 'webp') {
        $mime = 'image/webp';
    } elseif ($ext === 'pdf') {
        $mime = 'application/pdf';
    }
}

$filename = basename($target);
$dispositionType = $inline ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($target));
header('Content-Disposition: ' . $dispositionType . '; filename="' . rawurlencode($filename) . '"');
header('X-Content-Type-Options: nosniff');
readfile($target);
exit;
