<?php
// Secure download/view for message attachments.
session_start();

if (!isset($_SESSION['logged-in']) || $_SESSION['logged-in'] == false) {
    http_response_code(403);
    exit('Not authorized.');
}

require_once __DIR__ . '/src/Database.php';
$db = Database::getInstance();

$user = $_SESSION['user'] ?? null;
$role = $user->role ?? 'member';
$officialRoles = ['mayor','deputymayor','spokesperson','chief_officer','info_officer'];
$isAdmin = ($role === 'admin');
$isOfficial = in_array($role, $officialRoles, true);

if (!$isAdmin && !$isOfficial) {
    http_response_code(403);
    exit('Not authorized.');
}

$recordType = isset($_GET['type']) && $_GET['type'] === 'reply' ? 'reply' : 'message';
$recordId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$asInline = isset($_GET['inline']) && $_GET['inline'] === '1';

if ($recordId < 1) {
    http_response_code(400);
    exit('Invalid request.');
}

$relative = null;
$filename = '';
$mime = 'application/octet-stream';
$size = 0;
$ownerRole = null;

if ($recordType === 'message') {
    $stmt = $db->prepare("SELECT id, recipient_role, attachment_path, attachment_name, attachment_type, attachment_size FROM messages WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        exit('Server error.');
    }
    $stmt->bind_param('i', $recordId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_object() : null;
    $stmt->close();

    if (!$row || empty($row->attachment_path)) {
        http_response_code(404);
        exit('Attachment not found.');
    }
    $ownerRole = $row->recipient_role;
    $relative = $row->attachment_path;
    $filename = $row->attachment_name ?: '';
    $mime = $row->attachment_type ?: $mime;
    $size = (int)($row->attachment_size ?: 0);
} else {
    // reply
    $stmt = $db->prepare("
        SELECT mr.id, mr.attachment_path, mr.attachment_name, mr.attachment_type, mr.attachment_size, m.recipient_role
        FROM messages_replies mr
        JOIN messages m ON mr.message_id = m.id
        WHERE mr.id = ?
    ");
    if (!$stmt) {
        http_response_code(500);
        exit('Server error.');
    }
    $stmt->bind_param('i', $recordId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_object() : null;
    $stmt->close();

    if (!$row || empty($row->attachment_path)) {
        http_response_code(404);
        exit('Attachment not found.');
    }
    $ownerRole = $row->recipient_role;
    $relative = $row->attachment_path;
    $filename = $row->attachment_name ?: '';
    $mime = $row->attachment_type ?: $mime;
    $size = (int)($row->attachment_size ?: 0);
}

if (!$isAdmin && $ownerRole !== $role) {
    http_response_code(403);
    exit('Not authorized to access this attachment.');
}

$filename = $filename !== '' ? $filename : basename($relative);
$baseDir = realpath(__DIR__ . '/data/message_uploads');
$target = realpath(__DIR__ . '/' . ltrim($relative, '/\\'));

if (!$baseDir || !$target || strpos($target, $baseDir) !== 0 || !is_file($target)) {
    http_response_code(404);
    exit('File missing.');
}

$size = $size > 0 ? $size : filesize($target);

header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
$dispositionType = $asInline ? 'inline' : 'attachment';
header('Content-Disposition: ' . $dispositionType . '; filename="' . rawurlencode($filename) . '"');
header('X-Content-Type-Options: nosniff');
readfile($target);
exit;
