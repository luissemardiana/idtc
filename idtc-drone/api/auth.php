<?php
/**
 * IDTC Drone - Admin Authentication API
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'check';

function loginAttemptKey(): string {
    return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

function ensureLoginLockTable(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS admin_login_locks (attempt_key CHAR(64) PRIMARY KEY, failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function currentLoginLock(PDO $pdo, string $key): ?array {
    $stmt = $pdo->prepare('SELECT failed_attempts, locked_until FROM admin_login_locks WHERE attempt_key = ?');
    $stmt->execute([$key]);
    $lock = $stmt->fetch();
    if ($lock && !empty($lock['locked_until']) && strtotime($lock['locked_until']) <= time()) {
        $clear = $pdo->prepare('DELETE FROM admin_login_locks WHERE attempt_key = ?');
        $clear->execute([$key]);
        return null;
    }
    return $lock ?: null;
}

// 1. Check Session Status
if ($action === 'check') {
    if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_auth_version'] ?? 0) === 2) {
        echo json_encode([
            'status' => 'success',
            'authenticated' => true,
            'username' => $_SESSION['admin_username'] ?? 'Admin',
            'csrf_token' => getCsrfToken()
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'authenticated' => false
        ]);
    }
    exit;
}

if ($action === 'login_lock_status') {
    try {
        $pdo = getDbConnection();
        if (!$pdo) throw new RuntimeException('Database tidak tersedia.');
        ensureLoginLockTable($pdo);
        $lock = currentLoginLock($pdo, loginAttemptKey());
        $remainingSeconds = $lock && !empty($lock['locked_until']) ? max(0, strtotime($lock['locked_until']) - time()) : 0;
        echo json_encode(['status' => 'success', 'locked' => $remainingSeconds > 0, 'remaining_seconds' => $remainingSeconds]);
    } catch (Throwable $e) {
        http_response_code(503);
        echo json_encode(['status' => 'error', 'message' => 'Status keamanan login tidak tersedia.']);
    }
    exit;
}

// 2. Admin Login Action
if ($action === 'login') {
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

    $password = trim($input['password'] ?? '');

    if (empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Password wajib diisi.']);
        exit;
    }

    $pdo = getDbConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal.']);
        exit;
    }

    try {
        ensureLoginLockTable($pdo);
        $attemptKey = loginAttemptKey();
        $lock = currentLoginLock($pdo, $attemptKey);
        if ($lock && !empty($lock['locked_until'])) {
            $remainingSeconds = max(0, strtotime($lock['locked_until']) - time());
            http_response_code(429);
            echo json_encode(['status' => 'error', 'locked' => true, 'remaining_seconds' => $remainingSeconds, 'message' => 'Anda kehilangan akses percobaan login selama 20 menit.']);
            exit;
        }
        $stmt = $pdo->query('SELECT * FROM admin_users ORDER BY id ASC LIMIT 1');
        $user = $stmt->fetch();

        $isValidPassword = $user && password_verify($password, $user['password'] ?? '');
        $usedLegacyPassword = false;

        // Migrate the legacy fixed password on its first successful use so it
        // cannot continue to bypass passwords set through the profile page.
        if (!$isValidPassword && $user && hash_equals('wecandoit', $password)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash !== false) {
                $update = $pdo->prepare('UPDATE admin_users SET password = ? WHERE id = ?');
                $update->execute([$newHash, (int)$user['id']]);
                $isValidPassword = true;
                $usedLegacyPassword = true;
            }
        }

        if ($isValidPassword) {
            $pdo->prepare('DELETE FROM admin_login_locks WHERE attempt_key = ?')->execute([$attemptKey]);
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_auth_version'] = 2;
            $_SESSION['admin_username'] = $user['username'] ?? 'Admin';
            $_SESSION['admin_user_id'] = $user['id'] ?? null;
            $_SESSION['admin_legacy_login'] = $usedLegacyPassword;

            echo json_encode([
                'status' => 'success',
                'message' => 'Login berhasil.',
                'username' => $_SESSION['admin_username'],
                'csrf_token' => getCsrfToken()
            ]);
            exit;
        }

        $pdo->prepare('INSERT INTO admin_login_locks (attempt_key, failed_attempts) VALUES (?, 1) ON DUPLICATE KEY UPDATE failed_attempts = failed_attempts + 1, locked_until = NULL')->execute([$attemptKey]);
        $lock = currentLoginLock($pdo, $attemptKey);
        $attempts = (int)($lock['failed_attempts'] ?? 1);
        if ($attempts >= 3) {
            $lockedUntil = date('Y-m-d H:i:s', time() + 1200);
            $pdo->prepare('UPDATE admin_login_locks SET failed_attempts = 3, locked_until = ? WHERE attempt_key = ?')->execute([$lockedUntil, $attemptKey]);
            http_response_code(429);
            echo json_encode(['status' => 'error', 'locked' => true, 'remaining_seconds' => 1200, 'message' => 'Anda kehilangan akses percobaan login selama 20 menit.']);
            exit;
        }

        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Password salah. Sisa percobaan: ' . (3 - $attempts) . '.']);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem auth: ' . $e->getMessage()]);
        exit;
    }
}

// 3. Admin Logout Action
if ($action === 'logout') {
    if (!empty($_SESSION['admin_logged_in'])) {
        requireCsrfToken();
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    echo json_encode([
        'status' => 'success',
        'message' => 'Logout berhasil.'
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal']);
?>
