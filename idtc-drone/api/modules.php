<?php
/** Public read / protected CRUD API for IDTC content modules. */
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? 'list';
$tables = [
    'training' => ['table' => 'training_programs', 'fields' => ['title','subtitle','description','duration','target_audience','features','image_url','status','display_order']],
    'services' => ['table' => 'services', 'fields' => ['title','category','subtitle','description','image_url','full_case_study','metrics','status','display_order']],
    'gallery' => ['table' => 'gallery_items', 'fields' => ['category','title','caption','image_url','display_size','status','display_order']],
    'testimonials' => ['table' => 'testimonials', 'fields' => ['customer_name','role','testimonial','photo_url','status','display_order']],
    'credentials' => ['table' => 'credentials', 'fields' => ['category','org_name','relationship','image_url','status','display_order']],
    'certifications' => ['table' => 'certifications', 'fields' => ['category','title','description','image_url','status','display_order']],
];
if (!isset($tables[$module])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Modul tidak valid.']); exit; }
$pdo = getDbConnection();
if (!$pdo) { http_response_code(503); echo json_encode(['status'=>'error','message'=>'Database tidak tersedia. Jalankan setup_database.php terlebih dahulu.']); exit; }
$meta = $tables[$module];
$isAdmin = !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_auth_version'] ?? 0) === 2;

function ensureCredentialsTable(PDO $pdo): void {
    // Some installations predate the Credentials module and never ran the schema
    // migration, leaving TRUSTED & RECOGNIZED on static fallback only. Creating the
    // table here (once) makes the module fully CRUD-able; seeding matches the static
    // fallback so the public section stays identical until the admin edits content.
    $tableAlreadyExists = (bool) $pdo->query("SHOW TABLES LIKE 'credentials'")->fetchColumn();
    $pdo->exec("CREATE TABLE IF NOT EXISTS credentials (
        id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL, org_name VARCHAR(180) NOT NULL,
        relationship VARCHAR(255) NULL, image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active',
        display_order INT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_credentials_public (status, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Seed only when the table is created for the first time. An empty existing
    // table means an admin intentionally removed every item.
    if (!$tableAlreadyExists && (int)$pdo->query('SELECT COUNT(*) FROM credentials')->fetchColumn() === 0) {
        $seedItems = [
            ['REGULATORY & AVIATION', 'DKPPU / Kemenhub RI', 'Regulasi & Keselamatan Penerbangan', 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?q=80&w=300&auto=format&fit=crop'],
            ['REGULATORY & AVIATION', 'AirNav Indonesia', 'Izin Wilayah Udara & NOTAM', 'https://images.unsplash.com/photo-1519074069444-1ba4edd16be1?q=80&w=300&auto=format&fit=crop'],
            ['REGULATORY & AVIATION', 'Disnaker Kab. Sleman', 'Instansi Mitra In-House Training', 'https://images.unsplash.com/photo-1577495508048-b635879837f1?q=80&w=300&auto=format&fit=crop'],
            ['CERTIFICATION & PROFESSIONAL STANDARDS', 'Sertifikasi BNSP / Pilot', 'Standar Kompetensi Pilot Drone', 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=300&auto=format&fit=crop'],
            ['CERTIFICATION & PROFESSIONAL STANDARDS', 'FASI (Aero Sport Indonesia)', 'Standar Navigasi & Olahraga Udara', 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=300&auto=format&fit=crop'],
            ['CERTIFICATION & PROFESSIONAL STANDARDS', 'ISO Safety Flight SOP', 'Standard Operasional & Prosedur Risk Management', 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=300&auto=format&fit=crop'],
            ['INDUSTRY & TECHNOLOGY PARTNERS', 'DJI Enterprise Platform', 'Armada Wahana Udara & Sensor', 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=300&auto=format&fit=crop'],
            ['INDUSTRY & TECHNOLOGY PARTNERS', 'Pix4D & Agisoft Photogrammetry', 'Software Pengolahan Data Spasial', 'https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=300&auto=format&fit=crop'],
            ['INDUSTRY & TECHNOLOGY PARTNERS', 'PT Jogja Creative Production', 'Perusahaan Induk (Parent Enterprise)', 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=300&auto=format&fit=crop']
        ];
        $insert = $pdo->prepare("INSERT INTO credentials (category, org_name, relationship, image_url, status, display_order) VALUES (?, ?, ?, ?, 'active', ?)");
        foreach ($seedItems as $i => $item) $insert->execute([$item[0], $item[1], $item[2], $item[3], $i + 1]);
    }
}

function ensureCertificationsTable(PDO $pdo): void {
    // Certification cards shown on the homepage CERTIFICATION section. The table
    // is created once here for databases that predate the module; the seed keeps
    // the section populated with the flagship certification until the admin edits.
    $tableAlreadyExists = (bool) $pdo->query("SHOW TABLES LIKE 'certifications'")->fetchColumn();
    $pdo->exec("CREATE TABLE IF NOT EXISTS certifications (
        id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL, title VARCHAR(180) NOT NULL,
        description TEXT NOT NULL, image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active',
        display_order INT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_certifications_public (status, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Do not recreate a certification an admin has deleted. Seed data belongs
    // only to a brand-new table.
    if (!$tableAlreadyExists && (int)$pdo->query('SELECT COUNT(*) FROM certifications')->fetchColumn() === 0) {
        $insert = $pdo->prepare("INSERT INTO certifications (category, title, description, image_url, status, display_order) VALUES (?, ?, ?, ?, 'active', ?)");
        $insert->execute([
            'PILOT QUALIFICATION',
            'IDTC Remote Pilot Qualification',
            'Penilaian standar terbang mencakup kelaikan wahana, SOP darurat, dan manajemen risiko ruang udara.',
            'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1200&auto=format&fit=crop',
            1
        ]);
    }
}

function removeManagedGalleryFile(string $url): void {
    // Clean up files we manage (gallery photos, credentials logos, cert images).
    foreach (['uploads/gallery/', 'uploads/credentials/', 'uploads/certifications/', 'uploads/testimonials/'] as $prefix) {
        if (strpos($url, $prefix) !== 0) continue;
        $path = __DIR__ . '/../' . $prefix . basename($url);
        if (is_file($path)) @unlink($path);
    }
}

if ($action === 'list') {
    try {
        // Credentials table may be missing on databases created before the module
        // existed — create + seed it once so the admin CRUD always has a real table.
        if ($module === 'credentials') { try { ensureCredentialsTable($pdo); } catch (PDOException $e) {} }
        if ($module === 'certifications') { try { ensureCertificationsTable($pdo); } catch (PDOException $e) {} }
        $where = $isAdmin && ($_GET['status'] ?? '') === 'all' ? '' : " WHERE status = 'active'";
        $stmt = $pdo->query("SELECT * FROM {$meta['table']}{$where} ORDER BY display_order ASC, id DESC");
        echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]); exit;
    } catch (PDOException $e) {
        http_response_code(503);
        echo json_encode(['status'=>'error','message'=>'Tabel modul belum tersedia. Jalankan setup_database.php terlebih dahulu.']); exit;
    }
}
if (!$isAdmin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Akses admin diperlukan.']); exit; }
requireCsrfToken();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($action === 'upload') {
    // Gallery photos (DOKUMENTASI LAPANGAN), Certifications images, and Credentials logos
    // (NETWORK & CREDENTIALS) are uploaded separately and stored in their own folders.
    $uploadFolders = [
        'gallery' => ['dir' => 'uploads/gallery', 'prefix' => 'gallery_', 'label' => 'gambar'],
        'credentials' => ['dir' => 'uploads/credentials', 'prefix' => 'logo_', 'label' => 'logo'],
        'certifications' => ['dir' => 'uploads/certifications', 'prefix' => 'cert_', 'label' => 'gambar'],
        'testimonials' => ['dir' => 'uploads/testimonials', 'prefix' => 'testimonial_', 'label' => 'foto'],
    ];
    if (!isset($uploadFolders[$module])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Upload tidak tersedia untuk modul ini.']); exit; }
    $uploadLabel = $uploadFolders[$module]['label'];
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Pilih file gambar terlebih dahulu.']); exit; }
    $file = $_FILES['image'];
    if ($file['size'] < 1 || $file['size'] > 5 * 1024 * 1024) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Ukuran file maksimal 5 MB.']); exit; }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Gunakan file gambar JPG, PNG, atau WEBP yang valid.']); exit; }
    $relDir = $uploadFolders[$module]['dir'];
    $dir = __DIR__ . '/../' . $relDir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'Folder upload tidak dapat dibuat.']); exit; }
    $filename = $uploadFolders[$module]['prefix'] . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'Gagal menyimpan gambar.']); exit; }
    echo json_encode(['status'=>'success','message'=>'File berhasil diupload.','image_url'=>$relDir . '/' . $filename]); exit;
}

// ============ GALLERY FOLDER MANAGEMENT (DOKUMENTASI LAPANGAN) ============
// Folders are admin-managed category names. Each folder groups gallery photos;
// renaming/deleting a folder applies to every photo stored under that category.
if ($module === 'gallery' && in_array($action, ['folder_list', 'folder_save', 'folder_delete'], true)) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_folders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(120) NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Adopt categories that already exist on gallery photos so every folder is manageable.
        $pdo->exec("INSERT IGNORE INTO gallery_folders (category) SELECT DISTINCT category FROM gallery_items");
    } catch (PDOException $e) { /* table already exists or DB locked — keep going */ }
    $slugify = static function (string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    };

    if ($action === 'folder_list') {
        $byCategory = [];
        foreach ($pdo->query('SELECT id, category FROM gallery_folders ORDER BY category') as $row) {
            $byCategory[$row['category']] = ['id' => (int)$row['id'], 'category' => $row['category'], 'count' => 0];
        }
        foreach ($pdo->query('SELECT category, COUNT(*) AS c FROM gallery_items GROUP BY category') as $row) {
            if (!isset($byCategory[$row['category']])) $byCategory[$row['category']] = ['id' => null, 'category' => $row['category'], 'count' => 0];
            $byCategory[$row['category']]['count'] = (int)$row['c'];
        }
        echo json_encode(['status' => 'success', 'data' => array_values($byCategory)]); exit;
    }

    if ($action === 'folder_save') {
        $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
        $oldCategory = trim((string)($input['old_category'] ?? ''));
        $rawName = trim((string)($input['category'] ?? ''));
        if ($rawName === '') { http_response_code(422); echo json_encode(['status' => 'error', 'message' => 'Nama folder wajib diisi.']); exit; }
        $category = $slugify($rawName);
        if ($category === '') { http_response_code(422); echo json_encode(['status' => 'error', 'message' => 'Nama folder tidak valid.']); exit; }
        $existsInFolders = static function (string $cat) use ($pdo): bool { $s = $pdo->prepare('SELECT COUNT(*) FROM gallery_folders WHERE category = ?'); $s->execute([$cat]); return (bool)(int)$s->fetchColumn(); };
        $existsInItems = static function (string $cat) use ($pdo): bool { $s = $pdo->prepare('SELECT COUNT(*) FROM gallery_items WHERE category = ?'); $s->execute([$cat]); return (bool)(int)$s->fetchColumn(); };
        try {
            if ($id) {
                $oldStmt = $pdo->prepare('SELECT category FROM gallery_folders WHERE id = ? FOR UPDATE');
                $oldStmt->execute([$id]);
                $old = $oldStmt->fetchColumn();
                if (!$old) { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Folder tidak ditemukan.']); exit; }
                if ($category !== $old && ($existsInFolders($category) || $existsInItems($category))) { http_response_code(422); echo json_encode(['status' => 'error', 'message' => 'Nama folder sudah digunakan folder lain. Pilih nama lain.']); exit; }
                if ($category !== $old) {
                    $pdo->beginTransaction();
                    $pdo->prepare('UPDATE gallery_folders SET category = ? WHERE id = ?')->execute([$category, $id]);
                    $pdo->prepare('UPDATE gallery_items SET category = ? WHERE category = ?')->execute([$category, $old]);
                    $pdo->commit();
                }
                echo json_encode(['status' => 'success', 'message' => 'Folder berhasil diganti namanya.', 'category' => $category]); exit;
            }
            if ($oldCategory !== '') {
                // Rename a folder that only exists as a category on photos (no folder row yet).
                if ($existsInFolders($category) || $existsInItems($category)) { http_response_code(422); echo json_encode(['status' => 'error', 'message' => 'Nama folder sudah digunakan folder lain. Pilih nama lain.']); exit; }
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO gallery_folders (category) VALUES (?)')->execute([$category]);
                $pdo->prepare('UPDATE gallery_items SET category = ? WHERE category = ?')->execute([$category, $oldCategory]);
                $pdo->prepare('DELETE FROM gallery_folders WHERE category = ?')->execute([$oldCategory]);
                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'Folder berhasil diganti namanya.', 'category' => $category]); exit;
            }
            if ($existsInFolders($category) || $existsInItems($category)) { http_response_code(422); echo json_encode(['status' => 'error', 'message' => 'Folder dengan nama itu sudah ada.']); exit; }
            $pdo->prepare('INSERT INTO gallery_folders (category) VALUES (?)')->execute([$category]);
            echo json_encode(['status' => 'success', 'message' => 'Folder berhasil ditambahkan.', 'category' => $category]); exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan folder.']); exit;
        }
    }

    if ($action === 'folder_delete') {
        $category = trim((string)($input['category'] ?? ''));
        if ($category === '') { http_response_code(422); echo json_encode(['status' => 'error', 'message' => 'Folder tidak valid.']); exit; }
        try {
            $fileStmt = $pdo->prepare('SELECT image_url FROM gallery_items WHERE category = ?');
            $fileStmt->execute([$category]);
            foreach ($fileStmt->fetchAll(PDO::FETCH_COLUMN) as $url) removeManagedGalleryFile($url);
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM gallery_items WHERE category = ?')->execute([$category]);
            $pdo->prepare('DELETE FROM gallery_folders WHERE category = ?')->execute([$category]);
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Folder beserta isinya berhasil dihapus.']); exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus folder.']); exit;
        }
    }
}

if ($action === 'save') {
    $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    $values = [];
    foreach ($meta['fields'] as $field) {
        $value = trim((string)($input[$field] ?? ''));
        if ($field === 'display_order') $value = $value === '' ? 0 : (int)$value;
        if ($field === 'status' && !in_array($value, ['active','draft'], true)) $value = 'draft';
        if ($field === 'display_size' && !in_array($value, ['small','medium','large'], true)) $value = 'medium';
        $values[$field] = $value;
    }
    // Wajib diisi untuk semua modul CRUD.
    $required = ['training'=>['title','description'], 'services'=>['title','description'], 'gallery'=>['category','title'], 'testimonials'=>['customer_name','testimonial'], 'credentials'=>['category','org_name'], 'certifications'=>['category','title','description']][$module];
    foreach ($required as $field) if ($values[$field] === '') { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Field wajib belum lengkap.']); exit; }
    try {
        $pdo->beginTransaction();
        if ($id) {
            $old = null;
            $existing = null;
            if (in_array($module, ['gallery','credentials','certifications'], true)) { $oldStmt = $pdo->prepare('SELECT image_url FROM ' . $meta['table'] . ' WHERE id = ? FOR UPDATE'); $oldStmt->execute([$id]); $old = $oldStmt->fetchColumn() ?: null; }
            if ($module === 'testimonials') { $oldStmt = $pdo->prepare('SELECT photo_url FROM testimonials WHERE id = ? FOR UPDATE'); $oldStmt->execute([$id]); $old = $oldStmt->fetchColumn() ?: null; }
            if ($module === 'gallery') { $orderStmt = $pdo->prepare('SELECT category, display_order FROM gallery_items WHERE id = ? FOR UPDATE'); $orderStmt->execute([$id]); $existing = $orderStmt->fetch(); }
            if ($module === 'gallery' && $existing) {
                $oldOrder = (int)$existing['display_order'];
                $newOrder = (int)$values['display_order'];
                $oldCategory = $existing['category'];
                if ($newOrder < 1) $newOrder = $oldOrder;
                // Moving an existing image swaps it with the image at its target
                // position. Category changes keep each category independently ordered.
                if ($newOrder !== $oldOrder || $values['category'] !== $oldCategory) {
                    $target = $pdo->prepare('SELECT id FROM gallery_items WHERE category = ? AND display_order = ? AND id != ? LIMIT 1 FOR UPDATE');
                    $target->execute([$values['category'], $newOrder, $id]); $targetId = $target->fetchColumn();
                    if ($targetId) { $swap = $pdo->prepare('UPDATE gallery_items SET display_order = ? WHERE id = ?'); $swap->execute([$oldOrder, $targetId]); }
                    $values['display_order'] = $newOrder;
                }
            }
            $sets = implode(', ', array_map(fn($f) => "$f = ?", $meta['fields']));
            $stmt = $pdo->prepare("UPDATE {$meta['table']} SET $sets WHERE id = ?");
            $stmt->execute([...array_values($values), $id]);
            $newMediaUrl = $module === 'testimonials' ? $values['photo_url'] : ($values['image_url'] ?? '');
            if (in_array($module, ['gallery','credentials','certifications','testimonials'], true) && $old && $old !== $newMediaUrl) removeManagedGalleryFile($old);
        } else {
            if ($module === 'gallery') {
                $max = $pdo->prepare('SELECT COALESCE(MAX(display_order), 0) FROM gallery_items WHERE category = ?'); $max->execute([$values['category']]); $maxOrder = (int)$max->fetchColumn();
                $wanted = (int)$values['display_order'];
                if ($wanted < 1 || $wanted > $maxOrder + 1) $wanted = $maxOrder + 1;
                else { $shift = $pdo->prepare('UPDATE gallery_items SET display_order = display_order + 1 WHERE category = ? AND display_order >= ?'); $shift->execute([$values['category'], $wanted]); }
                $values['display_order'] = $wanted;
            }
            $columns = implode(',', $meta['fields']); $marks = implode(',', array_fill(0, count($meta['fields']), '?'));
            $stmt = $pdo->prepare("INSERT INTO {$meta['table']} ($columns) VALUES ($marks)");
            $stmt->execute(array_values($values));
            $id = $pdo->lastInsertId();
        }
        $pdo->commit();
        echo json_encode(['status'=>'success','message'=>'Konten berhasil disimpan.','id'=>$id]); exit;
    } catch (PDOException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); http_response_code(500); echo json_encode(['status'=>'error','message'=>'Gagal menyimpan konten.']); exit; }
}
if ($action === 'delete') {
    $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$id) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'ID tidak valid.']); exit; }
    $old = null;
    if (in_array($module, ['gallery','credentials','certifications'], true)) { $oldStmt = $pdo->prepare('SELECT image_url FROM ' . $meta['table'] . ' WHERE id = ?'); $oldStmt->execute([$id]); $old = $oldStmt->fetchColumn() ?: null; }
    if ($module === 'testimonials') { $oldStmt = $pdo->prepare('SELECT photo_url FROM testimonials WHERE id = ?'); $oldStmt->execute([$id]); $old = $oldStmt->fetchColumn() ?: null; }
    $stmt = $pdo->prepare("DELETE FROM {$meta['table']} WHERE id = ?"); $stmt->execute([$id]);
    if (in_array($module, ['gallery','credentials','certifications','testimonials'], true) && $old) removeManagedGalleryFile($old);
    echo json_encode(['status'=>'success','message'=>'Konten berhasil dihapus.']); exit;
}
http_response_code(400); echo json_encode(['status'=>'error','message'=>'Aksi tidak dikenal.']);
?>
