<?php
/**
 * IDTC — Admin profile photo endpoint.
 * - save_photo: accepts a photo taken from the device camera (base64 data URI)
 *   or picked from disk (multipart "photo"). Stored under uploads/admin/.
 * - remove_photo: deletes the current photo and resets to the default avatar.
 */
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();
$action = $_GET['action'] ?? 'save_photo';
$isAdmin = !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_auth_version'] ?? 0) === 2;

if (!$isAdmin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Akses admin diperlukan.']); exit; }
if (!in_array($action, ['save_photo', 'remove_photo', 'verify_current_password', 'change_password'], true)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Aksi tidak dikenal.']); exit; }
if (!$pdo) { http_response_code(503); echo json_encode(['status'=>'error','message'=>'Database tidak tersedia.']); exit; }
requireCsrfToken();

// Older installs may not have the photo column yet — add it on the fly.
try {
    $columnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'photo'");
    $columnCheck->execute();
    if (!(int)$columnCheck->fetchColumn()) $pdo->exec('ALTER TABLE admin_users ADD COLUMN photo VARCHAR(255) NULL');
} catch (Throwable $e) { /* ignore */ }

$stmt = $pdo->prepare('SELECT id, username, password, photo FROM admin_users WHERE username = ?');
$stmt->execute([$_SESSION['admin_username'] ?? 'admin']);
$admin = $stmt->fetch();
if (!$admin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Akun admin tidak ditemukan.']); exit; }

function removeManagedAdminPhoto(string $oldPhoto): void {
    if (strpos($oldPhoto, 'uploads/admin/') === 0) {
        $path = __DIR__ . '/../' . $oldPhoto;
        if (is_file($path)) @unlink($path);
    }
}

function verifyAdminCurrentPassword(array $admin, string $password): bool {
    if ($password === '') return false;
    if (password_verify($password, $admin['password'] ?? '')) return true;
    // Earlier versions accepted this login password directly while the seeded
    // database record still contained its initial password hash. Allow that
    // one-time migration path only for an unchanged default record.
    $isLegacyPassword = hash_equals('wecandoit', $password);
    $isDefaultDatabasePassword = password_verify('admin123', $admin['password'] ?? '');
    return $isLegacyPassword && (!empty($_SESSION['admin_legacy_login']) || $isDefaultDatabasePassword);
}

if ($action === 'verify_current_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method Not Allowed']); exit; }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = $_POST;
    $currentPassword = (string)($input['current_password'] ?? '');

    if (!verifyAdminCurrentPassword($admin, $currentPassword)) {
        unset($_SESSION['password_reset_verified_at'], $_SESSION['password_reset_verified_user_id']);
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Password lama salah. Akses reset ditolak.']);
        exit;
    }

    $_SESSION['password_reset_verified_at'] = time();
    $_SESSION['password_reset_verified_user_id'] = (int)$admin['id'];
    echo json_encode(['status'=>'success', 'message'=>'Password lama benar.']);
    exit;
}

if ($action === 'change_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method Not Allowed']); exit; }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = $_POST;

    $newPassword = (string)($input['new_password'] ?? '');
    $confirmPassword = (string)($input['confirm_password'] ?? '');

    $verifiedAt = (int)($_SESSION['password_reset_verified_at'] ?? 0);
    $verifiedUserId = (int)($_SESSION['password_reset_verified_user_id'] ?? 0);
    if ($verifiedUserId !== (int)$admin['id'] || $verifiedAt < time() - 600) {
        unset($_SESSION['password_reset_verified_at'], $_SESSION['password_reset_verified_user_id']);
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Verifikasi password lama diperlukan sebelum reset.']);
        exit;
    }

    if ($newPassword === '' || $confirmPassword === '') { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Password baru dan konfirmasi wajib diisi.']); exit; }
    if (strlen($newPassword) < 8 || strlen($newPassword) > 72) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Password baru harus terdiri dari 8–72 karakter.']); exit; }
    if (!hash_equals($newPassword, $confirmPassword)) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Konfirmasi password baru tidak sama.']); exit; }
    if (password_verify($newPassword, $admin['password'] ?? '')) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Password baru harus berbeda dari password saat ini.']); exit; }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    if ($hash === false) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'Gagal memproses password baru.']); exit; }
    $update = $pdo->prepare('UPDATE admin_users SET password = ? WHERE id = ?');
    $update->execute([$hash, (int)$admin['id']]);
    unset($_SESSION['password_reset_verified_at'], $_SESSION['password_reset_verified_user_id']);
    $_SESSION['admin_legacy_login'] = false;
    session_regenerate_id(true);
    echo json_encode(['status'=>'success', 'message'=>'Password login berhasil diperbarui.']);
    exit;
}

if ($action === 'remove_photo') {
    removeManagedAdminPhoto($admin['photo'] ?? '');
    $clear = $pdo->prepare('UPDATE admin_users SET photo = NULL WHERE id = ?');
    $clear->execute([(int)$admin['id']]);
    echo json_encode(['status'=>'success', 'message'=>'Foto profil dihapus.']);
    exit;
}

// ---- save_photo ----
// Accept either multipart file ("photo") or a base64 data URI ("image").
$raw = null;
if (!empty($_FILES['photo']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
    $raw = $_FILES['photo'];
} else {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $dataUri = $input['image'] ?? '';
    if (preg_match('#^data:image/(jpeg|jpg|png|webp);base64,(.*)$#is', $dataUri, $m)) {
        $binary = base64_decode($m[2], true);
        if ($binary === false || $binary === '') { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Data foto tidak valid.']); exit; }
        $tmp = tempnam(sys_get_temp_dir(), 'idtc_avatar_');
        if ($tmp === false || file_put_contents($tmp, $binary) === false) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'Gagal memproses foto.']); exit; }
        $raw = ['tmp_name' => $tmp, 'size' => strlen($binary), 'error' => UPLOAD_ERR_OK];
    }
}
if (!$raw) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Pilih foto terlebih dahulu.']); exit; }
if ((int)($raw['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Upload foto gagal.']); exit; }
if ((int)$raw['size'] < 1 || (int)$raw['size'] > 8 * 1024 * 1024) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Ukuran foto maksimal 8 MB.']); exit; }

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($raw['tmp_name']);
$extensions = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
if (!isset($extensions[$mime]) || !@getimagesize($raw['tmp_name'])) { @unlink($raw['tmp_name']); http_response_code(422); echo json_encode(['status'=>'error','message'=>'Gunakan file gambar JPG, PNG, atau WEBP yang valid.']); exit; }

$dir = __DIR__ . '/../uploads/admin';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) { @unlink($raw['tmp_name']); http_response_code(500); echo json_encode(['status'=>'error','message'=>'Folder upload tidak dapat dibuat.']); exit; }

$filename = 'admin_' . (int)$admin['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
$target = $dir . '/' . $filename;
$moved = false;
if (is_uploaded_file($raw['tmp_name'])) {
    $moved = move_uploaded_file($raw['tmp_name'], $target);
} elseif (@rename($raw['tmp_name'], $target) || @copy($raw['tmp_name'], $target)) {
    $moved = true;
}
@unlink($raw['tmp_name']);
if (!$moved) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'Gagal menyimpan foto.']); exit; }

removeManagedAdminPhoto($admin['photo'] ?? '');

$url = 'uploads/admin/' . $filename;
$update = $pdo->prepare('UPDATE admin_users SET photo = ? WHERE id = ?');
$update->execute([$url, (int)$admin['id']]);

echo json_encode(['status'=>'success', 'message'=>'Foto profil berhasil diperbarui.', 'photo' => $url]);
