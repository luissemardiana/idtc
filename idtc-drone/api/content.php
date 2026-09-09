<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'list';
$isAdmin = !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_auth_version'] ?? 0) === 2;

if (!$pdo) { http_response_code(503); echo json_encode(['status'=>'error','message'=>'Database tidak tersedia.']); exit; }
$pdo->exec("CREATE TABLE IF NOT EXISTS site_content (id INT AUTO_INCREMENT PRIMARY KEY, content_key VARCHAR(120) NOT NULL UNIQUE, section_name VARCHAR(80) NOT NULL, content_value TEXT NOT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, updated_by VARCHAR(100) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($action === 'list') {
    $stmt = $pdo->query('SELECT id, content_key, section_name, content_value, updated_at FROM site_content ORDER BY section_name, content_key');
    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]); exit;
}
if (!$isAdmin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Akses admin diperlukan.']); exit; }
requireCsrfToken();

if ($action === 'upload_hero_image') {
    if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Pilih gambar yang akan diunggah.']); exit; }
    $file = $_FILES['image'];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1024 * 1024) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Gambar gagal diunggah atau ukurannya melebihi 5 MB.']); exit; }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Format gambar harus JPG, PNG, atau WEBP.']); exit; }
    $relativeDir = 'uploads/hero';
    $directory = __DIR__ . '/../' . $relativeDir;
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'Folder gambar Hero tidak dapat dibuat.']); exit; }
    $filename = 'hero_' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'Gagal menyimpan gambar Hero.']); exit; }
    echo json_encode(['status'=>'success','message'=>'Gambar Hero berhasil diunggah. Klik Simpan Hero untuk menerapkan.', 'image_url'=>$relativeDir . '/' . $filename]); exit;
}
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($action === 'save') {
    $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    $key = strtolower(trim($input['content_key'] ?? ''));
    $section = trim($input['section_name'] ?? '');
    $value = trim($input['content_value'] ?? '');
    if (!preg_match('/^[a-z0-9-]{3,120}$/', $key) || !$section || !$value) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Key, bagian, dan isi wajib valid.']); exit; }
    $existing = $pdo->prepare('SELECT id FROM site_content WHERE content_key = ? LIMIT 1');
    $existing->execute([$key]);
    $existingId = (int) ($existing->fetchColumn() ?: 0);
    if ($existingId && (!$id || $existingId !== (int) $id)) {
        http_response_code(409);
        echo json_encode(['status'=>'error','message'=>'Konten dengan key ini sudah ada. Gunakan tombol Edit pada daftar konten untuk mengubahnya.']);
        exit;
    }
    if ($id) { $stmt=$pdo->prepare('UPDATE site_content SET content_key=?, section_name=?, content_value=?, updated_by=? WHERE id=?'); $stmt->execute([$key,$section,$value,$_SESSION['admin_username']??'admin',$id]); }
    else { $stmt=$pdo->prepare('INSERT INTO site_content (content_key,section_name,content_value,updated_by) VALUES (?,?,?,?)'); $stmt->execute([$key,$section,$value,$_SESSION['admin_username']??'admin']); }
    echo json_encode(['status'=>'success','message'=>'Konten halaman tersimpan.']); exit;
}
if ($action === 'delete') { $id=filter_var($input['id']??0,FILTER_VALIDATE_INT); if(!$id){http_response_code(422);echo json_encode(['status'=>'error','message'=>'ID tidak valid.']);exit;} $stmt=$pdo->prepare('DELETE FROM site_content WHERE id=?');$stmt->execute([$id]);echo json_encode(['status'=>'success','message'=>'Konten dihapus.']);exit; }
http_response_code(400); echo json_encode(['status'=>'error','message'=>'Aksi tidak dikenal.']);
?>
