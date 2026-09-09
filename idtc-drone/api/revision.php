<?php
/**
 * IDTC — Lightweight content revision endpoint for the public live-sync poller.
 * Returns a hash that changes whenever admin-managed public content is edited,
 * so visitor pages can detect changes without downloading full data each poll.
 */
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$pdo = getDbConnection();
if (!$pdo) {
    echo json_encode(['status' => 'success', 'revision' => null]);
    exit;
}

// Only public-facing rows matter, so admin draft edits do not reload visitors.
$publicTables = [
    'services'         => "WHERE status = 'active'",
    'training_programs'=> "WHERE status = 'active'",
    'testimonials'     => "WHERE status = 'active'",
    'credentials'      => "WHERE status = 'active'",
    'gallery_items'    => "WHERE status = 'active'",
    'articles'         => "WHERE status = 'published'",
];

$parts = [];
foreach ($publicTables as $table => $where) {
    try {
        $row = $pdo->query("SELECT COUNT(*) AS c, COALESCE(MAX(updated_at), '') AS u FROM `$table` $where")->fetch(PDO::FETCH_ASSOC);
        $parts[] = $table . ':' . $row['c'] . ':' . $row['u'];
    } catch (Throwable $e) {
        // Table may not exist yet on older installs (e.g. credentials) — skip it.
    }
}

// CMS values are included in the revision hash. This catches multiple saves
// made within the same second, when updated_at alone would be identical.
try {
    $cmsRows = $pdo->query('SELECT content_key, content_value, updated_at FROM site_content ORDER BY content_key')->fetchAll(PDO::FETCH_ASSOC);
    $parts[] = 'site_content:' . hash('sha256', json_encode($cmsRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
} catch (Throwable $e) {
    // The CMS table may not exist on a fresh installation.
}

echo json_encode(['status' => 'success', 'revision' => sha1(implode('|', $parts))]);
