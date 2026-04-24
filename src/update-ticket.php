<?php
require_once __DIR__ . '/security.php';
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

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'msg' => 'Method not allowed.',
        'status' => 405,
    ]);
    exit();
}

if (!csrf_validate_request()) {
    http_response_code(419);
    echo json_encode([
        'msg' => 'Security validation failed. Please refresh the page and try again.',
        'status' => 419,
    ]);
    exit();
}

if (!isset($_SESSION['logged-in']) || $_SESSION['logged-in'] != true || empty($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode([
        'msg' => 'Not authorized.',
        'status' => 403,
    ]);
    exit();
}

$user = $_SESSION['user'];
$role = $user->role ?? 'member';
$officialRoles = ['mayor', 'deputymayor', 'spokesperson', 'chief_officer', 'info_officer'];
$isAdmin = ($role === 'admin');
$isOfficial = in_array($role, $officialRoles, true);

if (!$isAdmin && !$isOfficial) {
    http_response_code(403);
    echo json_encode([
        'msg' => 'Not authorized.',
        'status' => 403,
    ]);
    exit();
}

require_once './Database.php';
$link = Database::getInstance();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = strtolower(trim((string)($_POST['status'] ?? '')));
$allowedStatuses = ['open', 'pending', 'closed', 'solved'];

if ($id < 1 || !in_array($status, $allowedStatuses, true)) {
    http_response_code(422);
    echo json_encode([
        'msg' => 'Invalid ticket update request.',
        'status' => 422,
    ]);
    exit();
}

$stmt = $link->prepare("UPDATE ticket SET status = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'msg' => 'Ticket status update failed.',
        'status' => 500,
    ]);
    exit();
}

$stmt->bind_param('si', $status, $id);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode([
        'msg' => 'Ticket status update failed.',
        'status' => 500,
    ]);
    exit();
}

echo json_encode([
    'msg' => $affected > 0 ? 'Ticket status changed.' : 'No ticket changes were needed.',
    'status' => 200,
]);
