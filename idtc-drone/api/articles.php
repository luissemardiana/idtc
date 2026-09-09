<?php
/**
 * IDTC Drone - Article Management Backend API
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();

// Execute auto-publishing check on every API call
autoPublishScheduledArticles($pdo);

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'list';
$isAdmin = !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_auth_version'] ?? 0) === 2;
$allowedCategories = ['Berita', 'Pelatihan', 'Teknologi', 'Event', 'Ops Lapangan'];

// Helper: Require Admin Access
function requireAdmin($isAdmin) {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Akses ditolak. Anda harus login sebagai admin.'
        ]);
        exit;
    }
}

if ($isAdmin && in_array($action, ['create', 'update', 'unpublish', 'delete', 'upload_image'], true)) {
    requireCsrfToken();
}

// Helper: Generate Unique Slug from Title
function generateSlug($title, $pdo, $currentId = 0) {
    // Basic slugify
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'artikel-' . time();
    }

    $originalSlug = $slug;
    $count = 1;

    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM articles WHERE slug = ? AND id != ? LIMIT 1");
        $stmt->execute([$slug, $currentId]);
        if (!$stmt->fetch()) {
            break; // Slug is unique
        }
        $slug = $originalSlug . '-' . $count;
        $count++;
    }

    return $slug;
}

// Helper: Format Datetime for Display (e.g. 4 Sep 2026 • 08:00 WIB)
function formatWibDate($datetimeStr) {
    if (!$datetimeStr) return '-';
    $timestamp = strtotime($datetimeStr);
    if (!$timestamp) return '-';

    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $day = date('j', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    $time = date('H:i', $timestamp);

    return "{$day} {$month} {$year} • {$time} WIB";
}

// -----------------------------------------------------------------------------
// 1. GET LIST ARTICLES
// -----------------------------------------------------------------------------
if ($action === 'list') {
    $statusFilter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'all';
    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';

    $params = [];
    $where = [];

    // Public / Visitor can ONLY view PUBLISHED articles
    if (!$isAdmin) {
        $where[] = "status = 'published'";
    } else {
        if ($statusFilter !== 'all' && in_array($statusFilter, ['draft', 'scheduled', 'published'])) {
            $where[] = "status = ?";
            $params[] = $statusFilter;
        }
    }

    if (!empty($search)) {
        $where[] = "(title LIKE ? OR category LIKE ? OR summary LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT * FROM articles {$whereSql} ORDER BY CASE WHEN display_order > 0 THEN display_order ELSE 999999999 END ASC, created_at DESC";

    try {
        if (!$pdo) {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        // Format dates for UI
        foreach ($articles as &$item) {
            $item['formatted_scheduled_at'] = formatWibDate($item['scheduled_at']);
            $item['formatted_published_at'] = formatWibDate($item['published_at']);
            $item['formatted_created_at']   = formatWibDate($item['created_at']);
            $item['formatted_updated_at']   = formatWibDate($item['updated_at']);
        }

        echo json_encode([
            'status' => 'success',
            'count' => count($articles),
            'data' => $articles
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// -----------------------------------------------------------------------------
// 2. GET SINGLE ARTICLE DETAIL (OR PREVIEW)
// -----------------------------------------------------------------------------
if ($action === 'detail') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!$id && !$slug) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Parameter ID atau slug diperlukan.']);
        exit;
    }

    try {
        if (!$pdo) {
            http_response_code(503);
            echo json_encode(['status' => 'error', 'message' => 'Database tidak tersedia.']);
            exit;
        }

        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
        }

        $article = $stmt->fetch();

        if (!$article) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Artikel tidak ditemukan.']);
            exit;
        }

        // Visitor protection: Non-admin CANNOT view draft or scheduled articles
        if (!$isAdmin && $article['status'] !== 'published') {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Artikel tidak ditemukan atau belum dipublikasikan.']);
            exit;
        }

        $article['formatted_scheduled_at'] = formatWibDate($article['scheduled_at']);
        $article['formatted_published_at'] = formatWibDate($article['published_at']);
        $article['formatted_created_at']   = formatWibDate($article['created_at']);
        $article['formatted_updated_at']   = formatWibDate($article['updated_at']);

        echo json_encode([
            'status' => 'success',
            'data' => $article
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// -----------------------------------------------------------------------------
// 3. CREATE ARTICLE (ADMIN ONLY)
// -----------------------------------------------------------------------------
if ($action === 'create') {
    requireAdmin($isAdmin);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
        exit;
    }

    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $title     = trim($input['title'] ?? '');
    $category  = trim($input['category'] ?? '');
    $author    = trim($input['author'] ?? 'Admin IDTC');
    $summary   = trim($input['summary'] ?? '');
    $content   = trim($input['content'] ?? '');
    $imageUrl  = trim($input['image_url'] ?? '');
    $status    = trim($input['status'] ?? 'draft'); // 'draft', 'scheduled', 'published'
    $scheduled = trim($input['scheduled_at'] ?? '');
    $requestedOrder = filter_var($input['display_order'] ?? null, FILTER_VALIDATE_INT);

    // Standard Validations
    if (empty($title) || empty($category) || empty($author) || empty($content)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Kolom Judul, Kategori, Penulis, dan Isi Artikel wajib diisi.']);
        exit;
    }

    if (mb_strlen($category) > 100) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Kategori artikel maksimal 100 karakter.']);
        exit;
    }

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['status' => 'error', 'message' => 'Database tidak tersedia.']);
        exit;
    }

    if (!in_array($status, ['draft', 'scheduled', 'published'])) {
        $status = 'draft';
    }

    $scheduledAt = null;
    $publishedAt = null;

    if ($status === 'scheduled') {
        if (empty($scheduled)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Tanggal dan jam publish wajib ditentukan untuk artikel Terjadwal.']);
            exit;
        }
        $ts = strtotime($scheduled);
        if (!$ts) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Format jadwal publish tidak valid.']);
            exit;
        }
        $scheduledAt = date('Y-m-d H:i:s', $ts);
        
        if ($ts <= time()) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Waktu terjadwal harus berada di masa depan. Gunakan Publish Sekarang untuk artikel yang langsung tayang.']);
            exit;
        }
    } elseif ($status === 'published') {
        $publishedAt = date('Y-m-d H:i:s');
    }

    $slug = generateSlug($title, $pdo);
    $createdBy = $_SESSION['admin_username'] ?? 'admin';

    try {
        $pdo->beginTransaction();
        $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(display_order), 0) FROM articles')->fetchColumn();
        if (!$requestedOrder || $requestedOrder < 1 || $requestedOrder > $maxOrder + 1) $displayOrder = $maxOrder + 1;
        else { $displayOrder = $requestedOrder; $shift = $pdo->prepare('UPDATE articles SET display_order = display_order + 1 WHERE display_order >= ?'); $shift->execute([$displayOrder]); }
        $stmt = $pdo->prepare("
            INSERT INTO articles 
            (title, slug, category, author, summary, content, image_url, display_order, status, scheduled_at, published_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $title,
            $slug,
            $category,
            $author,
            $summary,
            $content,
            $imageUrl,
            $displayOrder,
            $status,
            $scheduledAt,
            $publishedAt,
            $createdBy
        ]);

        $pdo->commit();

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'message' => 'Artikel berhasil disimpan.',
            'id' => $newId,
            'slug' => $slug,
            'article_status' => $status
        ]);
        exit;
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan artikel: ' . $e->getMessage()]);
        exit;
    }
}

// -----------------------------------------------------------------------------
// 4. UPDATE ARTICLE (ADMIN ONLY)
// -----------------------------------------------------------------------------
if ($action === 'update') {
    requireAdmin($isAdmin);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
        exit;
    }

    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $id        = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    $title     = trim($input['title'] ?? '');
    $category  = trim($input['category'] ?? '');
    $author    = trim($input['author'] ?? 'Admin IDTC');
    $summary   = trim($input['summary'] ?? '');
    $content   = trim($input['content'] ?? '');
    $imageUrl  = trim($input['image_url'] ?? '');
    $status    = trim($input['status'] ?? '');
    $scheduled = trim($input['scheduled_at'] ?? '');
    $requestedOrder = filter_var($input['display_order'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID artikel wajib diisi untuk update.']);
        exit;
    }

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['status' => 'error', 'message' => 'Database tidak tersedia.']);
        exit;
    }

    // Retrieve existing article
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Artikel tidak ditemukan.']);
        exit;
    }

    // Preserve existing values if not passed
    $title    = !empty($title) ? $title : $existing['title'];
    $category = !empty($category) ? $category : $existing['category'];
    $author   = !empty($author) ? $author : $existing['author'];
    $summary  = isset($input['summary']) ? $summary : $existing['summary'];
    $content  = !empty($content) ? $content : $existing['content'];
    $imageUrl = isset($input['image_url']) ? $imageUrl : $existing['image_url'];
    $status   = !empty($status) ? $status : $existing['status'];
    $displayOrder = $requestedOrder && $requestedOrder > 0 ? $requestedOrder : (int)($existing['display_order'] ?? 0);

    if (mb_strlen($category) > 100) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Kategori artikel maksimal 100 karakter.']);
        exit;
    }

    if (!in_array($status, ['draft', 'scheduled', 'published'])) {
        $status = $existing['status'];
    }

    $scheduledAt = $existing['scheduled_at'];
    $publishedAt = $existing['published_at'];

    // Handle Status Transition Logic
    if ($status === 'scheduled') {
        if (!empty($scheduled)) {
            $ts = strtotime($scheduled);
            if (!$ts) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Format jadwal publish tidak valid.']);
                exit;
            }
            if ($ts <= time()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Waktu terjadwal harus berada di masa depan. Gunakan Publish Sekarang untuk artikel yang langsung tayang.']);
                exit;
            }
            $scheduledAt = date('Y-m-d H:i:s', $ts);
        }
    } elseif ($status === 'published') {
        $publishedAt = date('Y-m-d H:i:s');
    } elseif ($status === 'draft') {
        // Unpublish / Draft transition: keep all content, clear active scheduled date if needed or preserve
    }

    // Regenerate slug if title changed
    $slug = $existing['slug'];
    if ($title !== $existing['title']) {
        $slug = generateSlug($title, $pdo, $id);
    }

    try {
        $pdo->beginTransaction();
        $oldOrder = (int)($existing['display_order'] ?? 0);
        if ($oldOrder < 1) { $oldOrder = (int)$pdo->query('SELECT COALESCE(MAX(display_order), 0) + 1 FROM articles')->fetchColumn(); }
        if ($displayOrder !== $oldOrder) {
            $target = $pdo->prepare('SELECT id FROM articles WHERE display_order = ? AND id != ? LIMIT 1 FOR UPDATE');
            $target->execute([$displayOrder, $id]); $targetId = $target->fetchColumn();
            if ($targetId) { $swap = $pdo->prepare('UPDATE articles SET display_order = ? WHERE id = ?'); $swap->execute([$oldOrder, $targetId]); }
        }
        $stmt = $pdo->prepare("
            UPDATE articles 
            SET title = ?, slug = ?, category = ?, author = ?, summary = ?, content = ?, image_url = ?, display_order = ?, status = ?, scheduled_at = ?, published_at = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $title,
            $slug,
            $category,
            $author,
            $summary,
            $content,
            $imageUrl,
            $displayOrder,
            $status,
            $scheduledAt,
            $publishedAt,
            $id
        ]);

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Perubahan artikel berhasil disimpan.',
            'id' => $id,
            'slug' => $slug,
            'article_status' => $status
        ]);
        exit;
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui artikel: ' . $e->getMessage()]);
        exit;
    }
}

// -----------------------------------------------------------------------------
// 5. UNPUBLISH ARTICLE (PUBLISHED -> DRAFT) (ADMIN ONLY)
// -----------------------------------------------------------------------------
if ($action === 'unpublish') {
    requireAdmin($isAdmin);

    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID artikel wajib diisi.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE articles 
            SET status = 'draft', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Artikel berhasil ditarik dari publikasi dan dikembalikan ke Draft. Seluruh data tetap tersimpan aman.'
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menarik artikel: ' . $e->getMessage()]);
        exit;
    }
}

// -----------------------------------------------------------------------------
// 6. DELETE ARTICLE (ADMIN ONLY)
// -----------------------------------------------------------------------------
if ($action === 'delete') {
    requireAdmin($isAdmin);

    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID artikel wajib diisi.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Artikel berhasil dihapus secara permanen.'
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus artikel: ' . $e->getMessage()]);
        exit;
    }
}

// -----------------------------------------------------------------------------
// 7. UPLOAD IMAGE (ADMIN ONLY)
// -----------------------------------------------------------------------------
if ($action === 'upload_image') {
    requireAdmin($isAdmin);

    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada file gambar yang diupload atau terjadi error upload.']);
        exit;
    }

    $file = $_FILES['image'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Ukuran file melebihi batas maksimal 5 MB.']);
        exit;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, WEBP, atau GIF.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newFilename = 'article_' . uniqid() . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $publicUrl = 'uploads/' . $newFilename;
        echo json_encode([
            'status' => 'success',
            'message' => 'Gambar berhasil diupload.',
            'image_url' => $publicUrl
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file gambar ke server.']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal']);
?>
