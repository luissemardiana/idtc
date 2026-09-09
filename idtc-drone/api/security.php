<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$isAdmin = !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_auth_version'] ?? 0) === 2;
if (!$isAdmin) { http_response_code(403); echo json_encode(['status' => 'error', 'message' => 'Akses admin diperlukan.']); exit; }

$browserId = $_COOKIE['idtc_admin_browser'] ?? '';
if (!is_string($browserId) || !preg_match('/^[a-f0-9]{32}$/', $browserId)) { echo json_encode(['status' => 'success', 'events' => []]); exit; }

try {
    $pdo = getDbConnection();
    if (!$pdo) throw new RuntimeException('Database tidak tersedia.');
    $lastId = (int)($_SESSION['admin_security_last_event_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, created_at FROM admin_security_events WHERE id > ? AND browser_id != ? AND event_type = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) ORDER BY id ASC');
    $stmt->execute([$lastId, $browserId, 'login_page_opened']);
    $events = $stmt->fetchAll();
    $maxId = $lastId;
    foreach ($events as $event) $maxId = max($maxId, (int)$event['id']);
    // Store only processed alert IDs, so an event arriving during this request
    // is picked up by the next polling cycle.
    $_SESSION['admin_security_last_event_id'] = $maxId;
    echo json_encode(['status' => 'success', 'events' => $events]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Notifikasi keamanan tidak tersedia.']);
}
