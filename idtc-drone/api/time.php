<?php
/**
 * IDTC — Current server time endpoint (Asia/Jakarta wall clock).
 *
 * The Indonesia clock widget fetches this once on page load so the live
 * WIB / WITA / WIT clocks are anchored to the server's clock instead of the
 * visitor's device clock. Returns the same instant regardless of timezone.
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

echo json_encode([
    'status' => 'success',
    'server_ms' => (int) round(microtime(true) * 1000),
    'timezone' => date_default_timezone_get(),
    'iso' => date('c')
]);
