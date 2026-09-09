<?php
require_once __DIR__ . '/../config.php';

// Record each browser that reaches the login page. The value is random and
// only lets the active admin distinguish this browser from another one.
$securityBrowserId = $_COOKIE['idtc_admin_browser'] ?? '';
if (!is_string($securityBrowserId) || !preg_match('/^[a-f0-9]{32}$/', $securityBrowserId)) {
    $securityBrowserId = bin2hex(random_bytes(16));
    $securityCookiePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
    setcookie('idtc_admin_browser', $securityBrowserId, ['expires' => time() + 31536000, 'path' => $securityCookiePath, 'httponly' => true, 'samesite' => 'Lax']);
}
try {
    $securityPdo = getDbConnection();
    if ($securityPdo) {
        $securityPdo->exec('CREATE TABLE IF NOT EXISTS admin_security_events (id BIGINT AUTO_INCREMENT PRIMARY KEY, browser_id CHAR(32) NOT NULL, event_type VARCHAR(40) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_admin_security_events (created_at), INDEX idx_admin_security_browser (browser_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $securityPdo->prepare('INSERT INTO admin_security_events (browser_id, event_type) VALUES (?, ?)')->execute([$securityBrowserId, 'login_page_opened']);
    }
} catch (Throwable $e) { /* Security notifications must never block login. */ }

if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_auth_version'] ?? 0) === 2) {
    header('Location: index.php');
    exit;
}
$redirect = ($_GET['redirect'] ?? '') === 'articles' ? 'articles/' : 'index.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | IDTC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/notify.js"></script>
    <style>
        body { min-width: 320px; background: #f3f1ec !important; font-family: 'Plus Jakarta Sans', sans-serif; }
        body main { position: relative; isolation: isolate; overflow: hidden; padding: 28px; background: radial-gradient(circle at 88% 8%, rgba(224,180,140,.24), transparent 26%), linear-gradient(135deg, #eeece6 0%, #f8f7f3 52%, #e8e4dc 100%); }
        body main::before { content: 'IDTC  /  ADMIN CONTROL CENTER'; position: absolute; z-index: -1; top: 9%; left: 50%; width: 100%; transform: translateX(-50%); color: rgba(91,80,62,.23); font-size: clamp(.65rem, 1.2vw, .82rem); font-weight: 800; letter-spacing: .24em; text-align: center; }
        body main::after { content: ''; position: absolute; z-index: -1; width: min(72vw, 720px); height: min(72vw, 720px); right: -27vw; bottom: -42vw; border: 1px solid rgba(113,99,76,.16); border-radius: 50%; box-shadow: 0 0 0 60px rgba(113,99,76,.035), 0 0 0 120px rgba(113,99,76,.02); }
        body main > section { position: relative; width: min(100%, 460px) !important; padding: 42px !important; border: 1px solid #e3ddd1 !important; border-radius: 28px !important; background: rgba(255,255,255,.9) !important; box-shadow: 0 22px 55px rgba(64,52,33,.12) !important; }
        body main > section::before { content: ''; display: block; width: 58px; height: 58px; margin-bottom: 28px; border: 1px solid #d49b70; border-radius: 16px; background: #fbf7ed url('../logo/Logo IDTC.png') center / 74% auto no-repeat; }
        body main > section > a { display: inline-flex; align-items: center; min-height: 24px; color: #796947 !important; font-size: .68rem !important; font-weight: 800 !important; letter-spacing: .13em !important; text-transform: uppercase; transition: color .2s; }
        body main > section > a:hover { color: #b96e3e !important; }
        body main > section > p:first-of-type { margin-top: 30px !important; color: #b96e3e !important; font-size: .64rem !important; letter-spacing: .25em !important; }
        body main > section h1 { margin-top: 10px !important; font-size: clamp(2rem, 4vw, 2.7rem) !important; line-height: 1 !important; letter-spacing: -.06em !important; }
        body main > section h1 + p { max-width: 330px; color: #716852 !important; font-size: .88rem !important; line-height: 1.65 !important; }
        #login-form { margin-top: 28px !important; padding: 24px; border: 1px solid #ebe3d4; border-radius: 18px; background: #fcfbf8; box-shadow: 0 12px 30px rgba(61,43,14,.06); }
        #login-form label { margin-bottom: 9px !important; color: #5f5643 !important; font-size: .65rem !important; letter-spacing: .15em !important; }
        #login-form input { min-height: 54px; border-radius: 12px !important; font-family: inherit; transition: border-color .2s, box-shadow .2s; }
        #login-form input:focus { border-color: #c98558 !important; box-shadow: 0 0 0 4px rgba(201,133,88,.12) !important; }
        #login-form button { min-height: 54px; border-radius: 12px !important; letter-spacing: .14em !important; transition: background .2s, transform .2s, box-shadow .2s; }
        #login-form button:hover { background: #b96e3e !important; box-shadow: 0 12px 24px rgba(185,110,62,.18); transform: translateY(-1px); }
        #login-message { margin-top: 12px; }
        @media (max-width: 560px) { body main { padding: 18px; } body main > section { padding: 30px 24px !important; } body main::before { top: 5%; left: 50%; font-size: .55rem; } }
    </style>
</head>
<body class="min-h-screen bg-[#fdfdfb] font-sans text-[#111111]">
    <main class="flex min-h-screen items-center justify-center p-5">
        <section class="w-full max-w-md rounded-[30px] border border-[#d8c9a0] bg-white p-7 shadow-2xl">
            <a href="../index.php" class="text-xs font-black uppercase tracking-[.16em] text-[#b78a2d] hover:text-[#ff5e00]">← Kembali ke Website</a>
            <p class="mt-8 text-[10px] font-black uppercase tracking-[.28em] text-[#ff5e00]">IDTC</p>
            <h1 class="mt-2 text-3xl font-black uppercase tracking-tight">Login Admin</h1>
            <p class="mt-2 text-sm font-medium leading-relaxed text-[#6a5b3a]">Masukkan password untuk mengakses Admin Control Center.</p>
            <form id="login-form" class="mt-7 space-y-4">
                <div id="login-controls">
                    <label for="password" class="mb-1 block text-[10px] font-black uppercase tracking-[.2em] text-[#6a5b3a]">Password</label>
                    <input id="password" type="password" required autofocus placeholder="Masukkan password" class="w-full rounded-xl border border-[#e3dccb] bg-white px-4 py-3 text-sm outline-none focus:border-[#b78a2d] focus:ring-2 focus:ring-[#b78a2d]/20">
                    <button class="mt-4 w-full rounded-xl bg-[#111] px-4 py-3 text-xs font-black uppercase tracking-[.18em] text-white transition hover:bg-[#ff5e00]">Masuk Admin</button>
                </div>
                <p id="login-message" class="hidden text-sm text-red-600"></p>
            </form>
        </section>
    </main>
    <script>
        const loginForm = document.getElementById('login-form');
        const loginControls = document.getElementById('login-controls');
        const loginMessage = document.getElementById('login-message');
        let loginUnlockTimer;

        function formatRemaining(seconds) {
            const minutes = Math.floor(seconds / 60);
            return `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
        }

        function setLoginLock(seconds) {
            clearInterval(loginUnlockTimer);
            loginControls.classList.add('hidden');
            const unlockAt = Date.now() + (seconds * 1000);
            const updateLockMessage = () => {
                const remaining = Math.max(0, Math.ceil((unlockAt - Date.now()) / 1000));
                if (!remaining) {
                    clearInterval(loginUnlockTimer);
                    loginControls.classList.remove('hidden');
                    notifyInline(loginMessage, 'Akses login sudah tersedia kembali. Silakan masukkan password.', 'success');
                    document.getElementById('password').focus();
                    return;
                }
                notifyInline(loginMessage, `Anda kehilangan akses percobaan login. Coba lagi dalam ${formatRemaining(remaining)}.`, 'error');
            };
            updateLockMessage();
            loginUnlockTimer = setInterval(updateLockMessage, 1000);
        }

        async function checkLoginLock() {
            try {
                const response = await fetch('../api/auth.php?action=login_lock_status', { credentials: 'same-origin' });
                const data = await response.json();
                if (data.status === 'success' && data.locked) setLoginLock(data.remaining_seconds);
            } catch (error) {}
        }

        loginForm.addEventListener('submit', async event => {
            event.preventDefault();
            try {
                const response = await fetch('../api/auth.php?action=login', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({password: document.getElementById('password').value}) });
                const data = await response.json();
                if (data.status === 'success') location.href = <?php echo json_encode($redirect); ?>;
                else if (data.locked) setLoginLock(data.remaining_seconds || 1200);
                else notifyInline(loginMessage, data.message || 'Password salah.', 'error');
            } catch (error) { notifyInline(loginMessage, 'Koneksi server gagal.', 'error'); }
        });
        checkLoginLock();
    </script>
</body>
</html>
