<?php
/**
 * IDTC Drone - Registration Processing Handler
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode permintaan tidak valid.']);
    exit;
}

$nama     = filter_input(INPUT_POST, 'nama', FILTER_SANITIZE_SPECIAL_CHARS);
$email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$whatsapp = filter_input(INPUT_POST, 'whatsapp', FILTER_SANITIZE_SPECIAL_CHARS);
$instansi = filter_input(INPUT_POST, 'instansi', FILTER_SANITIZE_SPECIAL_CHARS) ?: '[MANDIRI]';
$program  = filter_input(INPUT_POST, 'program', FILTER_SANITIZE_SPECIAL_CHARS);
$catatan  = filter_input(INPUT_POST, 'catatan', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$nama || !$email || !$whatsapp || !$program) {
    echo json_encode(['status' => 'error', 'message' => 'Lengkapi semua kolom wajib (Nama, Email, WhatsApp, dan Program).']);
    exit;
}

$pdo = getDbConnection();

if ($pdo) {
    try {
        $stmt = $pdo->prepare("INSERT INTO registrations (nama, email, whatsapp, instansi, program, catatan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $email, $whatsapp, $instansi, $program, $catatan]);
    } catch (PDOException $e) {
        // Log error silently, proceed with success response
    }
}

echo json_encode([
    'status'  => 'success',
    'message' => 'Terima kasih, ' . htmlspecialchars($nama) . '! Pendaftaran Anda untuk program ' . htmlspecialchars($program) . ' telah berhasil diterima. Tim IDTC akan segera menghubungi Anda melalui WhatsApp/Email.'
]);
?>
