<?php
/**
 * IDTC Drone - Standalone Cron / Scheduler Script for Automatic Article Publishing
 * Run via CLI: php cron_publish.php
 */
require_once __DIR__ . '/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This scheduler can only be run from the command line.');
}

header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();
if (!$pdo) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Koneksi database gagal.'
    ]);
    exit;
}

$updatedCount = autoPublishScheduledArticles($pdo);

echo json_encode([
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s T'),
    'articles_published' => $updatedCount,
    'message' => $updatedCount > 0 
        ? "Berhasil mempublikasikan {$updatedCount} artikel terjadwal."
        : "Tidak ada artikel terjadwal yang siap dipublikasikan saat ini."
]);
?>
