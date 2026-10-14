<?php
require_once __DIR__ . '/../config.php';
// Admin controls contain inline JavaScript; never serve an older cached copy
// after CRUD behavior is updated.
header('Cache-Control: no-store, max-age=0, must-revalidate');
header('Pragma: no-cache');

$isAdmin = !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_auth_version'] ?? 0) === 2;
if (!$isAdmin) {
    header('Location: login.php');
    exit;
}
$csrfToken = $isAdmin ? getCsrfToken() : '';
$adminProfile = null;
if ($isAdmin) {
    try {
        $profilePdo = getDbConnection();
        if ($profilePdo) {
            $profileStmt = $profilePdo->prepare('SELECT id, username, photo, created_at FROM admin_users WHERE username = ?');
            $profileStmt->execute([$_SESSION['admin_username'] ?? '']);
            $adminProfile = $profileStmt->fetch() ?: null;
        }
    } catch (Throwable $e) { $adminProfile = null; }
}
$adminInitial = (function_exists('mb_substr') ? mb_substr($_SESSION['admin_username'] ?? 'A', 0, 1) : substr($_SESSION['admin_username'] ?? 'A', 0, 1));
$initialModule = $_GET['module'] ?? 'dashboard';
if ($initialModule === 'articles') {
    header('Location: articles/');
    exit;
}
if (!in_array($initialModule, ['dashboard','hero','training','services','gallery','testimonials','credentials','certifications','findus','cms','profile'], true)) $initialModule = 'dashboard';

$moduleTitles = [
    'dashboard' => 'Dashboard',
    'hero' => 'Hero Beranda',
    'training' => 'Training Programs',
    'services' => 'Services',
    'gallery' => 'Dokumentasi Laporan (Galeri)',
    'testimonials' => 'Testimonials',
    'credentials' => 'Credentials',
    'certifications' => 'Certifications',
    'findus' => 'Find Us (Kontak)',
    'cms' => 'Site Content (CMS)',
    'profile' => 'Profil Admin'
];
$headingTitle = $moduleTitles[$initialModule] ?? 'Dashboard';
$isCrudModule = in_array($initialModule, ['training','services','gallery','testimonials','credentials','certifications'], true);
$isModuleActive = static function (string $name) use ($initialModule): bool { return $initialModule === $name; };
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Studio | IDTC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/notify.js"></script>
    <style>
        .nav-btn.active { background-color: rgba(255,255,255,0.1); border-left: 3px solid #FF5E00; }
        .module-section { display: none; }
        .module-section.active { display: block; }
        /* Layout CRUD & Section: form kiri 380px, konten/tabel kanan fleksibel */
        #crud-app,
        .content-layout {
            display: grid !important;
            grid-template-columns: 380px minmax(0, 1fr) !important;
            gap: 32px !important;
            width: 100% !important;
            align-items: start !important;
        }
        #crud-app > .crud-editor-column,
        #crud-app > .crud-data-column,
        .content-layout > .crud-editor-column,
        .content-layout > .crud-data-column {
            min-width: 0 !important;
            width: 100% !important;
        }
        #crud-app > .crud-editor-column,
        .content-layout > .crud-editor-column { grid-column: 1 !important; grid-row: 1 !important; }
        #crud-app > .crud-data-column,
        .content-layout > .crud-data-column { grid-column: 2 !important; grid-row: 1 !important; }
        @media (max-width: 900px) {
            #crud-app,
            .content-layout {
                grid-template-columns: 1fr !important;
            }
            #crud-app > .crud-data-column,
            .content-layout > .crud-data-column { grid-column: 1 !important; grid-row: 2 !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-[#FDFDFB] font-sans text-[#111111]">
<?php if (!$isAdmin): ?>
    <main class="flex min-h-screen items-center justify-center p-5">
        <section class="w-full max-w-md rounded-[30px] border border-[#D8C9A0] bg-white p-7 shadow-2xl">
            <a href="../index.php" class="text-xs font-black uppercase tracking-[0.16em] text-[#B78A2D] hover:text-[#FF5E00]">← Kembali ke website</a>
            <p class="mt-8 text-[10px] font-black uppercase tracking-[0.28em] text-[#FF5E00]">IDTC</p>
            <h1 class="mt-2 text-3xl font-black uppercase tracking-tight text-[#111111]">Admin Studio</h1>
            <p class="mt-2 text-sm leading-relaxed font-medium text-[#6A5B3A]">Masuk untuk mengelola konten website.</p>
            <form id="login-form" class="mt-7 space-y-4">
                <div>
                    <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A]" for="password">Password</label>
                    <input id="password" type="password" required class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 text-sm outline-none focus:border-[#B78A2D] focus:ring-2 focus:ring-[#B78A2D]/20" />
                </div>
                <p id="login-message" class="hidden text-sm text-red-600"></p>
                <button class="w-full rounded-xl bg-[#111111] px-4 py-3 text-xs font-black uppercase tracking-[0.18em] text-white transition hover:bg-[#FF5E00]">Login Admin</button>
            </form>
        </section>
    </main>
    <script>
        document.getElementById('login-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const message = document.getElementById('login-message');
            const response = await fetch('../api/auth.php?action=login', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({password: document.getElementById('password').value})
            });
            const data = await response.json();
            if (data.status === 'success') {
                const redirect = new URLSearchParams(window.location.search).get('redirect');
                location.href = redirect === 'articles' ? 'articles/' : 'index.php';
            }
            else { if (message) notifyInline(message, data.message || 'Login gagal. Periksa username/password.', 'error'); }
        });
    </script>
<?php else: ?>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="hidden w-64 shrink-0 flex-col bg-[#111111] text-white md:sticky md:top-0 md:flex md:h-screen overflow-y-auto">
            <div class="border-b border-white/5 px-6 pt-6 pb-5">
                <h1 class="text-2xl font-black uppercase tracking-tight">Control Center</h1>
                <p class="mt-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#B78A2D]">Indonesia Drone Training Center</p>
            </div>

            <nav class="flex-1 space-y-1 px-4 py-4">
                <button data-target="dashboard" class="nav-btn<?php echo $isModuleActive('dashboard') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></button>
                <button data-target="hero" class="nav-btn<?php echo $isModuleActive('hero') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg><span>Hero Beranda</span></button>
                <button data-target="training" class="nav-btn<?php echo $isModuleActive('training') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg><span>Training Programs</span></button>
                <button data-target="services" class="nav-btn<?php echo $isModuleActive('services') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg><span>Services</span></button>
                <button data-target="gallery" class="nav-btn<?php echo $isModuleActive('gallery') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg><span>Gallery</span></button>
                <button data-target="testimonials" class="nav-btn<?php echo $isModuleActive('testimonials') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span>Testimonials</span></button>
                <button data-target="credentials" class="nav-btn<?php echo $isModuleActive('credentials') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 7.7l5.4-.8z"/></svg><span>Credentials</span></button>
                <button data-target="certifications" class="nav-btn<?php echo $isModuleActive('certifications') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.5 13l2 7-5.5-3-5.5 3 2-7"/></svg><span>Certifications</span></button>
                <a href="articles/" data-target="articles" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5"/></svg><span>Artikel</span></a>
                <button data-target="findus" class="nav-btn<?php echo $isModuleActive('findus') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><span>Find Us (Kontak)</span></button>
                <button data-target="cms" class="nav-btn<?php echo $isModuleActive('cms') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h10"/></svg><span>Site Content (CMS)</span></button>
                <button data-target="profile" class="nav-btn<?php echo $isModuleActive('profile') ? ' active' : ''; ?> w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>Profil Admin</span></button>
            </nav>

            <div class="border-t border-white/10 p-5">
                <div class="flex items-center gap-3">
                    <?php if (!empty($adminProfile['photo'])): ?>
                    <img src="../<?php echo htmlspecialchars($adminProfile['photo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto profil" class="h-9 w-9 shrink-0 rounded-full border border-white/20 object-cover">
                    <?php else: ?>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#FF5E00] text-xs font-black uppercase text-white"><?php echo htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-white"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Administrator</p>
                    </div>
                </div>
            </div>

        </aside>

        <!-- Main Content -->
        <main class="min-w-0 flex-1 bg-[#FDFDFB] p-5 md:p-9 overflow-y-auto">
            <div class="mb-4 md:hidden">
                <select id="mobile-module-select" aria-label="Pilih menu" class="w-full rounded-xl border border-[#D8C9A0] bg-white px-3 py-2.5 text-sm font-bold uppercase tracking-wide text-[#111111]"></select>
            </div>
            <header class="page-header sticky top-0 z-20 flex flex-wrap items-center justify-between gap-4 rounded-b-2xl border-b border-[#E8E1D5] px-3 pb-5 pt-3 -mx-3 mb-6">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#FF5E00]">Admin Control Center</p>
                    <h2 id="heading" class="mt-1 text-2xl sm:text-3xl font-black uppercase tracking-tight text-[#111111]"><?php echo htmlspecialchars($headingTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="mt-1 text-xs font-semibold text-[#8A7B58]">Login sebagai <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="light-clock-wrap hidden rounded-2xl border border-[#E8E1D5] bg-white px-3 py-2 shadow-sm xl:block" title="Jam Indonesia — otomatis menyesuaikan WIB / WITA / WIT dengan lokasi Anda">
                        <div id="idtc-indonesia-clocks" class="flex items-center gap-1.5"></div>
                    </div>
                    <a href="../index.php" target="_blank" class="rounded-xl border border-[#D7CBA7] bg-white px-4 py-2.5 text-xs font-black uppercase tracking-[0.16em] text-[#111111] transition hover:border-[#111111] hover:bg-[#FBFAF7]">Lihat Website ↗</a>
                    <button id="logout-button" class="rounded-xl bg-[#111111] px-4 py-2.5 text-xs font-black uppercase tracking-[0.16em] text-white transition hover:bg-red-600">Logout</button>
                </div>
            </header>

            <!-- Dashboard Section -->
            <section id="sec-dashboard" class="module-section<?php echo $isModuleActive('dashboard') ? ' active' : ''; ?> space-y-6">
                <div class="overflow-hidden rounded-[30px] bg-[#111111] p-6 text-white sm:p-9 relative">
                    <p class="text-xs font-black uppercase tracking-[.24em] text-[#FF5E00] relative z-10">Versi Administrasi Website</p>
                    <div class="mt-3 relative z-10">
                        <h2 class="text-3xl font-black uppercase sm:text-5xl">IDTC Content Control</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-300">Kelola semua aspek konten website publik. Data yang Anda masukkan di sini akan secara otomatis sinkron dengan website utama.</p>
                    </div>
                </div>

                <div id="dashboard-stats" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mt-6">
                    <!-- Loaded dynamically -->
                </div>
            </section>

            <section id="sec-hero" class="module-section<?php echo $isModuleActive('hero') ? ' active' : ''; ?> w-full">
                <div class="content-layout w-full">
                    <div class="crud-editor-column">
                        <div class="rounded-[26px] border border-[#E8E1D5] bg-white p-5 sm:p-7 shadow-sm">
                            <div class="mb-5 border-b border-[#E8E1D5] pb-4">
                                <p class="text-xs font-black uppercase tracking-[.18em] text-[#B78A2D]">Hero Beranda</p>
                                <h2 class="mt-1 text-xl font-black uppercase tracking-tight">Kelola Hero</h2>
                                <p class="mt-1 text-xs text-slate-500">Teks, tombol, dan gambar latar website.</p>
                            </div>
                            <form id="hero-form" class="space-y-4">
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[.2em] text-[#6A5B3A]" for="hero-headline-input">Headline</label><textarea id="hero-headline-input" rows="4" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2 text-sm leading-relaxed" placeholder="Satu kata atau baris pada setiap baris">INDONESIA
DRONE
TRAINING
CENTER</textarea></div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[.2em] text-[#6A5B3A]" for="hero-subtitle-input">Subjudul</label><input id="hero-subtitle-input" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2 text-sm"></div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[.2em] text-[#6A5B3A]" for="hero-background-input">URL gambar latar (opsional)</label><input id="hero-background-input" type="url" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2 text-sm" placeholder="https://..."></div>
                                <div class="rounded-xl border border-dashed border-[#D8C9A0] bg-[#FFFCF7] p-3"><label class="block text-xs font-bold text-[#111111]" for="hero-image-file">Unggah gambar background</label><input id="hero-image-file" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1.5 block w-full text-xs"><p class="mt-1 text-[11px] text-slate-500">JPG, PNG, atau WEBP, maks 5 MB.</p><p id="hero-upload-message" class="mt-1 text-xs"></p></div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[.2em] text-[#6A5B3A]" for="hero-desc-input">Deskripsi</label><textarea id="hero-desc-input" rows="2" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2 text-sm"></textarea></div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[.2em] text-[#6A5B3A]" for="hero-primary-label-input">Teks tombol utama</label><input id="hero-primary-label-input" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2 text-sm"></div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[.2em] text-[#6A5B3A]" for="hero-primary-link-input">Tautan tombol utama</label><input id="hero-primary-link-input" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2 text-sm" placeholder="#tab-content-training"></div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[.2em] text-[#6A5B3A]" for="hero-secondary-label-input">Teks tombol kedua</label><input id="hero-secondary-label-input" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2 text-sm"></div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[.2em] text-[#6A5B3A]" for="hero-secondary-link-input">Tautan tombol kedua</label><input id="hero-secondary-link-input" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2 text-sm" placeholder="https://wa.me/..."></div>
                                <div class="flex flex-wrap items-center gap-2 pt-2"><button class="rounded-xl bg-[#111111] px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-white transition hover:bg-[#FF5E00]">Simpan Hero</button><button id="hero-reset" type="button" class="rounded-xl border border-[#E3DCCB] px-4 py-3 text-xs font-bold transition hover:bg-[#FBFAF7]">Reset</button><a href="../index.php#hero" target="_blank" class="rounded-xl border border-[#D7CBA7] px-4 py-3 text-xs font-bold text-center">Lihat Hero ↗</a></div>
                                <span id="hero-message" class="text-sm block mt-2"></span>
                            </form>
                        </div>
                    </div>
                    <div class="crud-data-column">
                        <div class="rounded-[26px] border border-[#E8E1D5] bg-white p-6 shadow-sm space-y-5">
                            <div class="border-b border-[#E8E1D5] pb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#B78A2D]">PRATINJAU TAMPILAN</p>
                                    <h3 class="text-lg font-black uppercase tracking-tight text-[#111111]">Gambar & Banner Hero</h3>
                                </div>
                                <span class="rounded-md bg-emerald-100 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-800">Live Preview</span>
                            </div>
                            <div>
                                <img id="hero-image-preview" alt="Preview gambar Hero" class="h-64 w-full rounded-2xl border border-[#E8E1D5] object-cover shadow-inner">
                            </div>
                            <div class="rounded-xl border border-[#E8E1D5] bg-[#FBFAF7] p-5 space-y-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#FF5E00]">IDTC · DRONE TRAINING</p>
                                <h4 class="text-xl font-black uppercase tracking-tight text-[#111111]">INDONESIA DRONE TRAINING CENTER</h4>
                                <p class="text-xs leading-relaxed text-slate-600">Perubahan teks headline, subjudul, dan background pada form di samping akan langsung diterapkan pada hero banner halaman utama website.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dynamic CRUD Section (Training, Services, Gallery, Testimonials, Credentials, Certifications) -->
            <section id="sec-crud" class="module-section<?php echo $isCrudModule ? ' active' : ''; ?> w-full">
                <div id="crud-app" class="content-layout w-full">
                    <!-- Loaded dynamically -->
                </div>
            </section>
<section id="sec-cms" class="module-section<?php echo $isModuleActive('cms') ? ' active' : ''; ?> w-full">
                <div class="content-layout w-full">
                    <div class="crud-editor-column">
                        <div class="rounded-[26px] border border-[#E8E1D5] bg-white p-5 sm:p-7 shadow-sm">
                            <div class="mb-5">
                                <p class="text-xs font-black uppercase tracking-[.18em] text-[#B78A2D]">CMS Halaman Utama</p>
                                <h2 class="text-xl font-black uppercase tracking-tight">Kelola Section</h2>
                                <p class="mt-1 text-xs text-slate-500">Ubah teks statis seperti kontak, alamat, dan deskripsi beranda.</p>
                            </div>
                            <form id="cms-form" class="space-y-4">
                                <input id="cms-id" type="hidden">
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A]">Bagian</label><select id="cms-section" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm"><option>Project & Training</option><option>Certification</option><option>Gallery</option><option>Artikel</option><option>FAQ</option><option>Explore Training</option><option>Find Us</option></select></div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A]">Key konten</label><input id="cms-key" list="cms-keys" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm" placeholder="Contoh: gallery-title"><datalist id="cms-keys"><option value="expertise-title"><option value="expertise-desc"><option value="projects-title"><option value="projects-subtitle"><option value="training-title"><option value="training-desc"><option value="certification-title"><option value="gallery-title"><option value="gallery-desc"><option value="news-title"><option value="news-desc"><option value="faq-title"><option value="faq-desc"><option value="contact-email"><option value="contact-whatsapp"><option value="contact-address"><option value="contact-instagram"></datalist></div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A]">Isi teks</label><textarea id="cms-value" required rows="4" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm"></textarea></div>
                                <div class="flex items-center gap-3 pt-2"><button type="submit" class="rounded-xl bg-[#B78A2D] px-5 py-3 font-bold text-white text-sm hover:bg-[#111111] transition">Simpan Konten</button><button id="cms-reset" type="button" class="rounded-xl border border-[#E3DCCB] px-4 py-3 font-bold text-sm text-slate-600">Konten Baru</button></div>
                                <span id="cms-message" class="text-sm block mt-2"></span>
                            </form>
                        </div>
                    </div>
                    <div class="crud-data-column">
                        <div class="overflow-hidden rounded-[26px] border border-[#E8E1D5] bg-white shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[500px] text-left text-sm"><thead class="bg-[#FBFAF7] text-[10px] font-black uppercase tracking-wider text-[#6A5B3A]"><tr><th class="p-4">Bagian</th><th class="p-4">Key</th><th class="p-4">Isi</th><th class="p-4 text-right">Aksi</th></tr></thead><tbody id="cms-list" class="divide-y divide-[#E8E1D5]"></tbody></table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Find Us (Kontak) Section -->
            <section id="sec-findus" class="module-section<?php echo $isModuleActive('findus') ? ' active' : ''; ?> w-full">
                <div class="content-layout w-full">
                    <div class="crud-editor-column">
                        <div class="rounded-[26px] border border-[#E8E1D5] bg-white p-5 sm:p-7 shadow-sm">
                            <div class="mb-5 border-b border-[#E8E1D5] pb-4">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#B78A2D]">CMS HALAMAN UTAMA — FIND US</p>
                                <h2 class="text-xl font-black uppercase tracking-tight">Kelola Kontak</h2>
                                <p class="mt-1 text-xs text-slate-500">Email, WhatsApp, alamat studio, dan Instagram.</p>
                            </div>
                            <form id="findus-form" class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A]">Email</label>
                                    <input id="findus-email" type="email" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm" placeholder="jogjacreativeproduction@gmail.com">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A]">WhatsApp (nomor tampilan)</label>
                                    <input id="findus-whatsapp" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm" placeholder="+62 856-0060-4388">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A]">Alamat Studio</label>
                                    <textarea id="findus-address" required rows="3" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm" placeholder="Perum Jl. Griya Mlati Indah No. B4, Mulungan Kulon, Sendangadi, Kec. Mlati, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55285"></textarea>
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A]">Instagram</label>
                                    <input id="findus-instagram" required class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5 text-sm" placeholder="@idtc.drone">
                                </div>
                                <div class="pt-2">
                                    <button class="w-full rounded-xl bg-[#111111] px-5 py-3 text-xs font-black uppercase tracking-[0.18em] text-white transition hover:bg-[#B78A2D]">Simpan Kontak</button>
                                    <p id="findus-message" class="text-sm mt-2"></p>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="crud-data-column">
                        <div class="rounded-[26px] border border-[#E8E1D5] bg-white p-6 shadow-sm space-y-5">
                            <div class="border-b border-[#E8E1D5] pb-4">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#B78A2D]">INFORMASI TAMPILAN</p>
                                <h3 class="text-lg font-black uppercase tracking-tight text-[#111111]">Lokasi & Kontak Aktif</h3>
                                <p class="mt-1 text-xs text-slate-500">Perubahan pada form di samping akan otomatis langsung mengupdate seluruh tampilan footer, tombol WhatsApp, dan bagian "FIND US" di website.</p>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-xl border border-[#E8E1D5] bg-[#FBFAF7] p-4">
                                    <p class="text-[10px] font-black uppercase tracking-wider text-[#6A5B3A]">Status Sinkronisasi</p>
                                    <p class="mt-1 font-bold text-sm text-emerald-700 flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Aktif & Terhubung</p>
                                </div>
                                <div class="rounded-xl border border-[#E8E1D5] bg-[#FBFAF7] p-4">
                                    <p class="text-[10px] font-black uppercase tracking-wider text-[#6A5B3A]">Integrasi Peta</p>
                                    <p class="mt-1 font-bold text-sm text-[#111111]">Google Maps & Rute</p>
                                </div>
                            </div>
                            <div class="rounded-xl border border-dashed border-[#D8C9A0] bg-[#FFFDF6] p-4 text-xs text-[#6A5B3A] space-y-1">
                                <p class="font-bold text-[#111111]">Petunjuk Pengisian:</p>
                                <p>• Nomor WhatsApp disarankan diawali kode negara (mis. +62 856-0060-4388).</p>
                                <p>• Alamat studio akan otomatis menjadi kata kunci pencarian peta di halaman publik.</p>
                                <p>• Akun Instagram dapat ditulis dengan awalan @ (mis. @idtc.drone).</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profile (Akun Admin) Section -->
            <section id="sec-profile" class="module-section<?php echo $isModuleActive('profile') ? ' active' : ''; ?> space-y-8">
                <div class="grid gap-7 lg:grid-cols-[340px_1fr]">
                    <div class="rounded-[26px] border border-[#E8E1D5] bg-white p-6 text-center sm:p-8">
                        <div class="relative mx-auto h-36 w-36">
                            <?php if (!empty($adminProfile['photo'])): ?>
                            <img id="profile-avatar-preview" src="../<?php echo htmlspecialchars($adminProfile['photo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto profil" class="h-36 w-36 rounded-full border-4 border-[#E8E1D5] object-cover shadow-sm">
                            <div id="profile-avatar-initial" class="hidden h-36 w-36 items-center justify-center rounded-full bg-[#FF5E00] text-5xl font-black uppercase text-white shadow-sm"><?php echo htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php else: ?>
                            <img id="profile-avatar-preview" src="" alt="Foto profil" class="hidden h-36 w-36 rounded-full border-4 border-[#E8E1D5] object-cover shadow-sm">
                            <div id="profile-avatar-initial" class="flex h-36 w-36 items-center justify-center rounded-full bg-[#FF5E00] text-5xl font-black uppercase text-white shadow-sm"><?php echo htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                        <h3 class="mt-6 text-xl font-black uppercase tracking-tight"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="mt-1 text-[10px] font-black uppercase tracking-[0.2em] text-[#B78A2D]">Administrator</p>
                    </div>

                    <div class="space-y-6 rounded-[26px] border border-[#E8E1D5] bg-white p-6 sm:p-8">
                        <div class="border-b border-[#E8E1D5] pb-4">
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#B78A2D]">Akun & Foto Profil</p>
                            <h2 class="mt-1 text-xl font-black uppercase tracking-tight">Ubah Foto Profil</h2>
                            <p class="mt-1 text-sm text-slate-500">Foto bisa diambil langsung dari kamera perangkat atau diunggah dari galeri/berkas. Foto akan tampil pada sidebar Control Center.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button id="profile-open-camera" type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#111111] px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-white transition hover:bg-[#FF5E00]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Ambil Foto (Kamera)</button>
                            <button id="profile-open-upload" type="button" class="inline-flex items-center gap-2 rounded-xl border border-[#D7CBA7] bg-white px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-[#111111] transition hover:bg-[#FBFAF7]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>Unggah Foto</button>
                            <button id="profile-remove-photo" type="button" class="<?php echo empty($adminProfile['photo']) ? 'hidden ' : ''; ?>inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-red-600 transition hover:bg-red-100"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Hapus Foto</button>
                            <input id="profile-file-input" type="file" accept="image/jpeg,image/png,image/webp" class="hidden">
                        </div>

                        <div id="profile-confirm-box" class="hidden rounded-xl border border-[#D8C9A0] bg-[#FFFDF6] p-4">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#B78A2D]">Konfirmasi Foto Baru</p>
                            <p class="mt-1 text-xs text-[#6A5B3A]">Periksa foto berikut sebelum disimpan ke profil Anda.</p>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <img id="profile-confirm-image" src="" alt="Pratinjau foto" class="h-20 w-20 rounded-full border-2 border-[#D8C9A0] object-cover">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button id="profile-confirm-save" type="button" class="rounded-xl bg-[#FF5E00] px-4 py-2.5 text-xs font-black uppercase tracking-[0.16em] text-white transition hover:bg-[#ff741a]">Simpan Foto</button>
                                    <button id="profile-confirm-cancel" type="button" class="rounded-xl border border-[#D7CBA7] bg-white px-4 py-2.5 text-xs font-black uppercase tracking-[0.16em] text-[#111111] transition hover:bg-[#FBFAF7]">Batal</button>
                                </div>
                            </div>
                        </div>

                        <p id="profile-message" class="text-sm"></p>

                    </div>
                </div>

                <div class="rounded-[26px] border border-[#E8E1D5] bg-white p-6 sm:p-8">
                    <div class="border-b border-[#E8E1D5] pb-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#B78A2D]">Keamanan Akun</p>
                        <h2 class="mt-1 text-xl font-black uppercase tracking-tight">Reset Password</h2>
                        <p class="mt-1 text-sm text-slate-500">Masukkan password saat ini, lalu buat password login baru untuk akun admin Anda.</p>
                    </div>

                    <form id="profile-password-verify-form" class="mt-6 grid gap-5 lg:grid-cols-3" novalidate>
                        <div>
                            <label for="profile-current-password" class="mb-2 block text-[10px] font-black uppercase tracking-[0.18em] text-[#6A5B3A]">Password Saat Ini</label>
                            <div class="relative">
                                <input id="profile-current-password" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 pr-12 text-sm outline-none focus:border-[#B78A2D] focus:ring-2 focus:ring-[#B78A2D]/20">
                                <button type="button" class="password-visibility-toggle absolute inset-y-0 right-0 px-4 text-[#6A5B3A] hover:text-[#111111]" data-password-target="profile-current-password" aria-label="Tampilkan password" title="Tampilkan password"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
                            </div>
                        </div>
                        <div class="flex items-end">
                            <button id="profile-password-verify" type="submit" class="rounded-xl bg-[#111111] px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-white transition hover:bg-[#FF5E00]">Verifikasi Password Lama</button>
                        </div>
                        <p id="profile-password-message" class="lg:col-span-3 text-sm"></p>
                    </form>

                    <form id="profile-password-form" class="mt-5 hidden grid gap-5 lg:grid-cols-2" novalidate>
                        <div>
                            <label for="profile-new-password" class="mb-2 block text-[10px] font-black uppercase tracking-[0.18em] text-[#6A5B3A]">Password Baru</label>
                            <div class="relative">
                                <input id="profile-new-password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 pr-12 text-sm outline-none focus:border-[#B78A2D] focus:ring-2 focus:ring-[#B78A2D]/20">
                                <button type="button" class="password-visibility-toggle absolute inset-y-0 right-0 px-4 text-[#6A5B3A] hover:text-[#111111]" data-password-target="profile-new-password" aria-label="Tampilkan password" title="Tampilkan password"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
                            </div>
                        </div>
                        <div>
                            <label for="profile-confirm-password" class="mb-2 block text-[10px] font-black uppercase tracking-[0.18em] text-[#6A5B3A]">Konfirmasi Password Baru</label>
                            <div class="relative">
                                <input id="profile-confirm-password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 pr-12 text-sm outline-none focus:border-[#B78A2D] focus:ring-2 focus:ring-[#B78A2D]/20">
                                <button type="button" class="password-visibility-toggle absolute inset-y-0 right-0 px-4 text-[#6A5B3A] hover:text-[#111111]" data-password-target="profile-confirm-password" aria-label="Tampilkan password" title="Tampilkan password"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
                            </div>
                        </div>
                        <div class="lg:col-span-2 flex flex-wrap items-center gap-4">
                            <button id="profile-password-submit" type="submit" class="rounded-xl bg-[#111111] px-5 py-3 text-xs font-black uppercase tracking-[0.16em] text-white transition hover:bg-[#FF5E00]">Simpan Password Baru</button>
                        </div>
                    </form>
                </div>

                <!-- Camera capture modal -->
                <div id="profile-camera-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/85 p-4">
                    <div class="w-full max-w-md overflow-hidden rounded-2xl border border-white/15 bg-[#151515] text-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-[#FF5E00]">Ambil Foto Profil</p>
                            <button type="button" id="profile-camera-close" class="text-slate-400 transition hover:text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                        </div>
                        <video id="profile-camera-video" autoplay playsinline muted class="h-72 w-full bg-black object-cover" style="transform: scaleX(-1) !important;"></video>
                        <div class="flex flex-wrap items-center justify-center gap-3 px-4 py-4">
                            <button type="button" id="profile-camera-flip" class="rounded-lg border border-white/15 px-3 py-2 text-[10px] font-black uppercase tracking-[0.14em] text-slate-200 transition hover:bg-white/5">Ganti Kamera</button>
                            <button type="button" id="profile-camera-capture" class="rounded-lg bg-[#FF5E00] px-5 py-2 text-xs font-black uppercase tracking-[0.14em] text-white transition hover:bg-[#ff741a]">Ambil Foto</button>
                            <button type="button" id="profile-camera-cancel" class="rounded-lg border border-white/15 px-4 py-2 text-xs font-black uppercase tracking-[0.14em] text-slate-200 transition hover:bg-white/5">Batal</button>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        const csrfToken = <?php echo json_encode($csrfToken); ?>;
        const escapeHtml = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
        const $ = id => document.getElementById(id);
        
        let currentModule = 'dashboard';
        let crudItems = [];
        let crudGalleryFilter = '';
        let galleryFolders = [];
        let galleryFolderRenameCat = null;
        let galleryExpandedGroups = new Set(); // daftar grup foto (folder+judul) yang sedang dibuka
        let cmsItems = [];
        let findUsRows = [];
        const findUsKeys = ['contact-email', 'contact-whatsapp', 'contact-address', 'contact-instagram'];
        const findUsFields = { 'contact-email': 'findus-email', 'contact-whatsapp': 'findus-whatsapp', 'contact-address': 'findus-address', 'contact-instagram': 'findus-instagram' };

        const moduleLabels = { dashboard: 'Dashboard', hero: 'Hero Beranda', training: 'Training Programs', services: 'Services', gallery: 'Gallery', testimonials: 'Testimonials', credentials: 'Credentials', certifications: 'Certifications', articles: 'Artikel', findus: 'Find Us (Kontak)', cms: 'Site Content (CMS)', profile: 'Profil Admin' };

        // Terapkan layout yang sama untuk semua modul CRUD: form kiri (380px), data kanan (minmax(0, 1fr))
        function enforceCrudSideBySide() {
            const app = $('crud-app');
            if (!app) return;
            const isDesktop = window.innerWidth > 900;
            app.style.setProperty('display', 'grid', 'important');
            app.style.setProperty('grid-template-columns', isDesktop ? '380px minmax(0, 1fr)' : '1fr', 'important');
            app.style.setProperty('grid-template-rows', 'auto', 'important');
            app.style.setProperty('gap', '32px', 'important');
            app.style.setProperty('align-items', 'start', 'important');
            app.style.setProperty('width', '100%', 'important');
            app.style.removeProperty('flex-direction');
            app.style.removeProperty('flex-wrap');
            const editor = app.querySelector('.crud-editor-column');
            const data = app.querySelector('.crud-data-column');
            if (editor) {
                editor.style.setProperty('grid-column', isDesktop ? '1' : '1', 'important');
                editor.style.setProperty('grid-row', '1', 'important');
                editor.style.setProperty('width', '100%', 'important');
                editor.style.setProperty('min-width', '0', 'important');
                editor.style.removeProperty('flex');
            }
            if (data) {
                data.style.setProperty('grid-column', isDesktop ? '2' : '1', 'important');
                data.style.setProperty('grid-row', isDesktop ? '1' : '2', 'important');
                data.style.setProperty('width', '100%', 'important');
                data.style.setProperty('min-width', '0', 'important');
                data.style.removeProperty('flex');
            }
        }
        window.addEventListener('resize', enforceCrudSideBySide);

        function statusBadge(status) {
            const tones = {
                active: 'bg-emerald-100 text-emerald-800',
                published: 'bg-emerald-100 text-emerald-800',
                scheduled: 'bg-amber-100 text-amber-800',
                draft: 'bg-slate-200 text-slate-600'
            }[status] || 'bg-[#F4F0E6] text-[#6A5B3A]';
            return `<span class="rounded-md px-2 py-1 text-[10px] font-black uppercase tracking-wider ${tones}">${escapeHtml(status)}</span>`;
        }        const crudFields = {
            training: [['title','Judul'],['subtitle','Subjudul'],['description','Deskripsi','textarea'],['duration','Durasi'],['target_audience','Target peserta'],['features','Fitur (satu per baris)','textarea'],['image_url','URL gambar'],['display_order','Urutan','number'],['status','Status','select']],
            services: [['title','Judul'],['category','Kategori'],['subtitle','Subjudul'],['description','Deskripsi','textarea'],['image_url','URL gambar'],['display_order','Urutan','number'],['status','Status','select']],
            // full_case_study & metrics dikelola lewat editor Case Study terstruktur (renderServicesCaseBlock).
            // Dokumen folders pada halaman user (DOKUMENTASI LAPANGAN / TRAINING GALLERY).
            // Kategori disimpan sebagai slug; folder publik dibuat dari kategori ini.
            gallery: [['category','Folder (pilih dari daftar)'],['caption','Caption'],['image_url','URL gambar'],['display_order','Urutan','number'],['status','Status','select']],
            testimonials: [['customer_name','Nama Alumni/Instansi'],['role','Peran/Instansi'],['testimonial','Testimoni','textarea'],['photo_url','URL foto (opsional)'],['display_order','Urutan','number'],['status','Status','select']],
            credentials: [['category','Kategori (mis. REGULATORY & AVIATION)'],['org_name','Nama Organisasi / Lembaga'],['relationship','Hubungan / Keterangan'],['image_url','URL gambar (opsional)'],['display_order','Urutan','number'],['status','Status','select']],
            certifications: [['category','Kategori / Badge (mis. PILOT QUALIFICATION)'],['title','Judul Sertifikasi'],['description','Deskripsi','textarea'],['image_url','URL gambar (opsional)'],['display_order','Urutan','number'],['status','Status','select']]
        };

        // ====== DOKUMENTASI LAPORAN (GALERI) — folder options & per-folder CRUD ======
        // Urutan/slug folder sama dengan pilihan folder pada halaman utama (TRAINING GALLERY).
        function galleryFolderLabel(value) { return String(value || '').replace(/[-_]+/g, ' ').replace(/\s+/g, ' ').trim().toUpperCase(); }
        function galleryCategoryOptions(current = '') {
            const seen = [];
            const add = cat => { cat = String(cat || '').trim(); if (cat && seen.indexOf(cat) === -1) seen.push(cat); };
            (galleryFolders || []).forEach(folder => add(folder.category));
            add(current);
            let opts = '<option value="">— Pilih Folder —</option>';
            seen.forEach(cat => { opts += `<option value="${escapeHtml(cat)}"${String(current) === cat ? ' selected' : ''}>${escapeHtml(galleryFolderLabel(cat))}</option>`; });
            return opts;
        }
        function galleryCategoryField(current = '') {
            return `<label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mb-1">Folder Gallery <span class="text-red-500">*</span></label>
                <select id="gallery-category-select" name="category" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5">${galleryCategoryOptions(current)}</select>
                <input id="gallery-category-new" type="text" placeholder="Atau ketik nama folder baru (mis. DRONE EXPO 2026)" class="mt-2 hidden w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5">
                <button type="button" id="gallery-category-new-toggle" class="mt-2 inline-flex items-center gap-1 text-xs font-black uppercase tracking-wider text-[#B78A2D] hover:text-[#8a6a1c]">➕ Folder Baru Lainnya…</button>`;
        }
        function wireGalleryNewFolderField() {
            const toggle = $('gallery-category-new-toggle');
            const input = $('gallery-category-new');
            if (toggle && input) toggle.addEventListener('click', () => { input.classList.remove('hidden'); toggle.classList.add('hidden'); input.focus(); });
        }

        // UI Navigation
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.target;
                if (!target || target === 'articles') return;
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                currentModule = target;

                // Simpan modul yang dipilih ke URL agar setelah refresh tetap di modul yang sama.
                try {
                    const nextUrl = new URL(window.location.href);
                    if (target === 'dashboard') nextUrl.searchParams.delete('module');
                    else nextUrl.searchParams.set('module', target);
                    history.replaceState(null, '', nextUrl.pathname + nextUrl.search);
                } catch (e) { /* URL tidak bisa diperbarui — abaikan */ }

                $('heading').textContent = moduleLabels[target];
                if (typeof mobileSelect !== 'undefined' && mobileSelect) mobileSelect.value = target;
                
                document.querySelectorAll('.module-section').forEach(s => s.classList.remove('active'));
                
                if (['training', 'services', 'gallery', 'testimonials', 'credentials', 'certifications'].includes(target)) {
                    $('sec-crud').classList.add('active');
                    loadCrudModule(target);
                } else {
                    $(`sec-${target}`).classList.add('active');
                    if (target === 'dashboard') loadDashboard();
                    else if (target === 'hero') loadHero();
                    else if (target === 'findus') loadFindUs();
                    else if (target === 'cms') loadCms();
                }
            });
        });

        // Sidebar keyboard navigation: arrows move and open each menu,
        // including the separate Artikel page.
        document.addEventListener('keydown', event => {
            if (!['ArrowUp', 'ArrowDown'].includes(event.key) || event.altKey || event.ctrlKey || event.metaKey) return;
            const activeElement = document.activeElement;
            if (activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName)) return;
            const sidebarItems = [...document.querySelectorAll('aside nav button, aside nav a')];
            if (!sidebarItems.length) return;
            event.preventDefault();
            const current = sidebarItems.indexOf(activeElement);
            const step = event.key === 'ArrowDown' ? 1 : -1;
            const next = current < 0 ? (step > 0 ? 0 : sidebarItems.length - 1) : (current + step + sidebarItems.length) % sidebarItems.length;
            const nextItem = sidebarItems[next];
            nextItem.focus();
            nextItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            try { sessionStorage.setItem('idtcSidebarKeyboardTarget', nextItem.dataset.target || 'dashboard'); } catch (e) {}
            nextItem.click();
        });

        document.addEventListener('keydown', event => {
            if (!['Enter', ' '].includes(event.key)) return;
            const sidebarItems = [...document.querySelectorAll('aside nav button, aside nav a')];
            if (!sidebarItems.includes(document.activeElement)) return;
            event.preventDefault();
            document.activeElement.click();
        });

        // Mobile module switcher (the sidebar is hidden below the md breakpoint)
        const mobileSelect = $('mobile-module-select');
        if (mobileSelect) {
            Object.keys(moduleLabels).forEach(key => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = moduleLabels[key];
                if (key === currentModule) option.selected = true;
                mobileSelect.appendChild(option);
            });
            mobileSelect.addEventListener('change', () => {
                if (mobileSelect.value === 'articles') {
                    location.href = 'articles/';
                    return;
                }
                const btn = document.querySelector(`.nav-btn[data-target="${mobileSelect.value}"]`);
                if (btn) btn.click();
            });
        }

        // 1. DASHBOARD
        async function loadDashboard() {
            const statsGrid = $('dashboard-stats');
            const keys = ['training', 'services', 'gallery', 'testimonials', 'credentials', 'certifications', 'articles'];
            const requests = keys.map(k => {
                const endpoint = k === 'articles'
                    ? '../api/articles.php?action=list&status=all'
                    : `../api/modules.php?module=${k}&action=list&status=all`;
                return fetch(endpoint).then(r => r.json());
            });
            
            const results = await Promise.all(requests);
            
            const statMeta = {
                training:     { color: '#B78A2D', icon: '<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/>' },
                services:     { color: '#FF5E00', icon: '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>' },
                gallery:      { color: '#0F766E', icon: '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>' },
                testimonials: { color: '#7C3AED', icon: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' },
                credentials:  { color: '#B45309', icon: '<path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 7.7l5.4-.8z"/>' },
                certifications:{ color: '#0E7490', icon: '<circle cx="12" cy="8" r="6"/><path d="M15.5 13l2 7-5.5-3-5.5 3 2-7"/>' },
                articles:     { color: '#FF5E00', icon: '<path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5"/>' }
            };
            const statCard = (key, count) => {
                const meta = statMeta[key] || { color: '#8A7B58', icon: '<circle cx="12" cy="12" r="10"/>' };
                const label = moduleLabels[key] || key;
                return `<div class="flex items-start gap-4 rounded-[26px] border border-[#E8E1D5] bg-white p-6 shadow-sm">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" style="background:${meta.color}14;color:${meta.color}">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">${meta.icon}</svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#6A5B3A]">${label}</p>
                        <b class="mt-1 block text-3xl font-black text-[#111111]">${count}</b>
                    </div>
                </div>`;
            };
            statsGrid.innerHTML = keys.map((k, i) => statCard(k, results[i].data?.length || 0)).join('');
        }

        // 2. CRUD MODULES (Training, Services, Gallery, Testimonials)
        function renderCrudInput(fieldDef, value = '') {
            const [name, label, type] = fieldDef;
            const safeVal = escapeHtml(value);

            // Gallery category is rendered as a custom folder selector block.
            if (currentModule === 'gallery' && name === 'category') return galleryCategoryField(value);

            // Longer multi-line text fields (descriptions, testimonials, etc.).
            if (type === 'textarea') {
                return `
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mb-1">${escapeHtml(label)}</label>
                    <textarea name="${escapeHtml(name)}" rows="3" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5">${safeVal}</textarea>
                `;
            }

            // Status selects always use the same active/draft options.
            if (type === 'select' && name === 'status') {
                return `
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mb-1">${escapeHtml(label)}</label>
                    <select name="${escapeHtml(name)}" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5">
                        <option value="active" ${value === 'active' ? 'selected' : ''}>Active</option>
                        <option value="draft" ${value === 'draft' ? 'selected' : ''}>Draft</option>
                    </select>
                `;
            }

            // Gallery display size is rendered as its own fixed select.
            if (type === 'size') {
                return `
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mb-1">${escapeHtml(label)}</label>
                    <select name="${escapeHtml(name)}" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5">
                        ${['small','medium','large'].map(q => `<option ${value===q?'selected':''}>${q}</option>`).join('')}
                    </select>
                `;
            }

            // Default text/number field.
            return `
                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#6A5B3A] mb-1">${escapeHtml(label)}</label>
                <input name="${escapeHtml(name)}" type="${type || 'text'}" value="${safeVal}" class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5">
            `;
        }

        // Structured Case Study editor for the Services/OUR PROJECTS module.
        // Saves into the existing full_case_study & metrics JSON columns, which
        // js/main.js parses to fill the "View Case Study" modal on the homepage.
        function renderServicesCaseBlock(editItem = {}) {
            let caseStudy = {};
            let metrics = [];
            try { caseStudy = editItem.full_case_study ? JSON.parse(editItem.full_case_study) : {}; } catch (e) { caseStudy = {}; }
            try { metrics = editItem.metrics ? JSON.parse(editItem.metrics) : []; } catch (e) { metrics = []; }
            if (!caseStudy || typeof caseStudy !== 'object' || Array.isArray(caseStudy)) caseStudy = {};
            if (!Array.isArray(metrics)) metrics = [];
            const caseText = (key, label) => `<div class="md:col-span-2"><label class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-[#6A5B3A]">${label}</label><textarea name="${key}" rows="2" placeholder="Opsional — kosongkan bila belum ada." class="w-full rounded-xl border border-[#E3DCCB] px-3 py-2.5">${escapeHtml(caseStudy[key] || '')}</textarea></div>`;
            const metricRows = (metrics.length ? metrics : [{}]).map(m => `<div class="metric-row flex flex-col sm:flex-row gap-2"><input class="metric-label flex-1 rounded-lg border border-[#E3DCCB] px-3 py-2 text-sm" placeholder="Label metrik (mis. Durasi Pelatihan)" value="${escapeHtml(m.label || '')}"><input class="metric-value flex-1 rounded-lg border border-[#E3DCCB] px-3 py-2 text-sm" placeholder="Nilai (mis. 5 Hari / 20 Peserta)" value="${escapeHtml(m.value || '')}"><button type="button" onclick="removeMetricRow(this)" class="rounded-lg border border-red-200 px-3 text-red-600 font-bold hover:bg-red-50 shrink-0" title="Hapus metrik">✕</button></div>`).join('');
            return `<div class="rounded-xl border border-[#E8E1D5] bg-[#fffaf0] p-4 sm:p-5">
                    <div class="border-b border-[#E8E1D5] pb-3">
                        <p class="text-xs font-black uppercase tracking-[.18em] text-[#B78A2D]">CASE STUDY PROYEK</p>
                        <h4 class="text-lg font-black">Konten Modal &ldquo;View Case Study&rdquo;</h4>
                        <p class="mt-1 text-xs text-slate-500">Isi di sini muncul saat pengunjung mengklik tombol &ldquo;View Case Study&rdquo; pada kartu proyek di bagian OUR PROJECTS halaman utama. Disimpan sebagai JSON (kolom full_case_study &amp; metrics).</p>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        ${caseText('cs_objective', 'TUJUAN PROYEK (Objective)')}
                        ${caseText('cs_technology', 'TEKNOLOGI & FLEET (Technology)')}
                        ${caseText('cs_workflow', 'ALUR WORKFLOW')}
                        ${caseText('cs_results', 'HASIL AKHIR & IMPACT (Results)')}
                    </div>
                    <div class="mt-5">
                        <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-[#6A5B3A]">METRIK UTAMA (IMPORTANT INFORMATION)</label>
                        <p class="mb-2 text-xs text-slate-500">Pasangan label &amp; nilai yang tampil pada panel informasi proyek (mis. Durasi, Jumlah Peserta, Lokasi).</p>
                        <div id="services-metrics-rows" class="space-y-2">${metricRows}</div>
                        <button type="button" onclick="addMetricRow()" class="mt-3 rounded-lg border border-[#B78A2D] bg-white px-3 py-2 text-xs font-bold text-[#B78A2D] hover:bg-[#B78A2D] hover:text-white transition">+ Tambah Metrik</button>
                    </div>
                </div>`;
        }

        function addMetricRow() {
            const wrap = $('services-metrics-rows');
            if (!wrap) return;
            const div = document.createElement('div');
            div.className = 'metric-row flex flex-col sm:flex-row gap-2';
            div.innerHTML = '<input class="metric-label flex-1 rounded-lg border border-[#E3DCCB] px-3 py-2 text-sm" placeholder="Label metrik (mis. Durasi Pelatihan)"><input class="metric-value flex-1 rounded-lg border border-[#E3DCCB] px-3 py-2 text-sm" placeholder="Nilai (mis. 5 Hari / 20 Peserta)"><button type="button" onclick="removeMetricRow(this)" class="rounded-lg border border-red-200 px-3 text-red-600 font-bold hover:bg-red-50 shrink-0" title="Hapus metrik">✕</button>';
            wrap.appendChild(div);
        }

        function removeMetricRow(button) {
            const row = button.closest('.metric-row');
            if (row) row.remove();
        }

        function crudFilteredRows() {
            if (currentModule !== 'gallery' || !crudGalleryFilter) return crudItems;
            return (crudItems || []).filter(x => String(x.category || '') === crudGalleryFilter);
        }
        // Folder registry = rows in gallery_folders (id) + any category found on photos,
        // ordered with the standard presets first then the rest alphabetically.
        function galleryFolderItems() {
            const counts = {};
            (crudItems || []).forEach(x => { const c = String(x.category || '').trim(); if (c) counts[c] = (counts[c] || 0) + 1; });
            const byCat = {};
            (galleryFolders || []).forEach(f => { byCat[f.category] = { id: f.id, category: f.category, description: f.description || '', cover_image_url: f.cover_image_url || '' }; });
            Object.keys(counts).forEach(c => { if (!byCat[c]) byCat[c] = { id: null, category: c, description: '', cover_image_url: '' }; });
            return Object.keys(byCat).map(key => byCat[key]).sort((a, b) => String(a.category).localeCompare(String(b.category))).map(f => {
                const photos = (crudItems || []).filter(item => String(item.category) === String(f.category));
                const activePhotos = photos.filter(item => item.status === 'active');
                const cover = f.cover_image_url || activePhotos[0]?.image_url || photos[0]?.image_url || '';
                const choices = (activePhotos.length ? activePhotos : photos).map(photo => `<option value="${escapeHtml(photo.image_url || '')}"${String(cover) === String(photo.image_url || '') ? ' selected' : ''}>Foto #${photo.id}</option>`).join('');
                return { ...f, count: counts[f.category] || 0, cover_image_url: cover, cover_options: choices };
            });
        }
        function galleryFolderManagerHtml() {
            if (currentModule !== 'gallery') return '';
            const folders = galleryFolderItems();
            const rows = folders.map(f => {
                const isEdit = galleryFolderRenameCat !== null && String(galleryFolderRenameCat) === String(f.category);
                const upCat = escapeHtml(galleryFolderLabel(f.category));
                const catAttr = escapeHtml(f.category);
                const rowActions = isEdit
                    ? `<button type="button" data-folder-rename-save class="mr-2 rounded-lg bg-[#B78A2D] px-3 py-1.5 text-xs font-black uppercase tracking-wider text-white hover:bg-[#8a6a1c]">Simpan</button>
                       <button type="button" data-folder-rename-cancel class="rounded-lg border border-[#E3DCCB] px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</button>`
                    : `<button type="button" data-folder-rename="${catAttr}" class="mr-3 font-bold text-[#B78A2D] hover:text-[#8a6a1c]">Edit</button>
                       <button type="button" data-folder-delete="${catAttr}" class="font-bold text-red-600 hover:text-red-700">Hapus</button>`;
                const nameCell = isEdit
                    ? `<input id="gallery-folder-rename-input" type="text" value="${catAttr}" data-old-category="${catAttr}" class="mb-2 w-full rounded-lg border border-[#B78A2D] bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20"><textarea id="gallery-folder-description-input" rows="2" placeholder="Keterangan folder yang tampil di halaman galeri" class="w-full rounded-lg border border-[#E3DCCB] bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20">${escapeHtml(f.description || '')}</textarea>`
                    : `<button type="button" data-folder-open="${catAttr}" class="text-left text-sm font-black uppercase tracking-wider text-[#111111] hover:text-[#FF5E00]" title="Buka isi folder">${upCat}</button><label class="mt-2 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Foto sampul<select data-folder-cover="${catAttr}" class="mt-1 block w-full rounded-lg border border-[#E3DCCB] bg-white px-2 py-1.5 text-xs"${f.cover_options ? '' : ' disabled'}>${f.cover_options || '<option value="">Belum ada foto</option>'}</select></label>${f.description ? `<p class="mt-1 text-xs text-slate-500">${escapeHtml(f.description)}</p>` : ''}`;
                return `<tr class="border-b border-[#F4EFE6] last:border-0">
                    <td class="px-4 py-2.5">${nameCell}<span class="ml-2 rounded-full bg-[#F4F0E6] px-2 py-0.5 text-[10px] font-bold text-[#8A7B58]">${f.count} foto</span></td>
                    <td class="px-4 py-2.5 text-right whitespace-nowrap">${rowActions}</td>
                </tr>`;
            }).join('');
            return `<div class="border-b border-[#E8E1D5] bg-[#fffaf0] px-5 py-3">
                <details open class="group">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 [&::-webkit-details-marker]:hidden">
                        <span class="text-[10px] font-black uppercase tracking-[0.18em] text-[#6A5B3A]">🗂 Kelola Folder Foto — tambah / ganti nama / hapus folder beserta isinya</span>
                        <span id="gallery-folder-manager-toggle" class="text-[9px] font-black uppercase tracking-wider text-[#B78A2D]">sembunyikan ▲</span>
                    </summary>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[11px] text-slate-500">${folders.length} folder terdaftar. Klik “Buka Isi” untuk melihat & mengelola foto folder tersebut.</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <input id="gallery-folder-new-name" type="text" placeholder="Nama folder baru (mis. Drone Expo)" class="w-52 rounded-lg border border-[#E3DCCB] bg-white px-3 py-2 text-sm focus:border-[#B78A2D] focus:outline-none">
                            <input id="gallery-folder-new-description" type="text" placeholder="Keterangan folder (opsional)" class="w-64 rounded-lg border border-[#E3DCCB] bg-white px-3 py-2 text-sm focus:border-[#B78A2D] focus:outline-none">
                            <button type="button" data-folder-add class="rounded-lg bg-[#111111] px-4 py-2 text-xs font-black uppercase tracking-wider text-white hover:bg-[#B78A2D]">＋ Tambah Folder</button>
                        </div>
                    </div>
                    <div class="mt-3 max-h-72 overflow-y-auto rounded-xl border border-[#E8E1D5] bg-white">
                        <table class="w-full text-sm">
                            <tbody>${rows || '<tr><td class="px-4 py-6 text-center text-slate-500">Belum ada folder. Tambahkan folder baru di atas.</td></tr>'}</tbody>
                        </table>
                    </div>
                </details>
            </div>`;
        }
        function galleryFolderChipsHtml() {
            if (currentModule !== 'gallery') return '';
            const folders = galleryFolderItems();
            const chip = (value, count) => {
                const label = value ? galleryFolderLabel(value) : 'Semua Folder';
                const active = String(crudGalleryFilter || '') === String(value || '');
                return `<button type="button" data-folder="${escapeHtml(value || '')}" class="gallery-folder-chip inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[10px] font-black uppercase tracking-wider transition ${active ? 'border-[#FF5E00] bg-[#FF5E00] text-white shadow-sm' : 'border-[#E3DCCB] bg-white text-[#6A5B3A] hover:border-[#B78A2D] hover:text-[#B78A2D]'}">${escapeHtml(label)}<span class="rounded-full px-1.5 text-[9px] ${active ? 'bg-white/25 text-white' : 'bg-[#F4F0E6] text-[#8A7B58]'}">${count}</span></button>`;
            };
            return `<div class="border-b border-[#E8E1D5] bg-[#FBFAF7] px-5 py-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#6A5B3A]">Filter Folder — klik folder untuk kelola foto per folder</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">${chip('', (crudItems || []).length)}${folders.map(f => chip(f.category, f.count)).join('')}</div>
                    </div>`;
        }
        // Foto aktif dikelompokkan per folder, tanpa melihat caption. Foto draf
        // selalu berdiri sendiri agar tidak bercampur dengan galeri publik.
        function galleryGroupKey(cat, status, id) { return String(cat || '').trim() + '\u0001' + (status === 'draft' ? 'draft:' + id : 'active'); }
        function galleryImgSrc(x) { const url = x.image_url || x.photo_url || ''; return url ? escapeHtml(url.startsWith('http') ? url : '../' + url) : ''; }
        function galleryGroupRows(rows) {
            if (!rows.length) return '';
            const groups = [];
            const index = {};
            rows.forEach(x => {
                const key = galleryGroupKey(x.category, x.status, x.id);
                if (index[key] === undefined) { index[key] = groups.length; groups.push({ cat: String(x.category || ''), status: x.status, items: [] }); }
                groups[index[key]].items.push(x);
            });
            const renderItemRow = photo => `<tr class="border-t border-[#F1EAE0] bg-[#FBFAF7]">
                    <td class="py-2 pl-10 pr-3">
                        <span class="inline-flex items-center gap-2.5">
                            <span class="inline-flex h-7 w-9 shrink-0 items-center justify-center overflow-hidden rounded border border-[#E8E1D5] bg-white">${photo.image_url ? `<img src="${galleryImgSrc(photo)}" alt="" class="h-full w-full object-cover">` : '<span class="text-[8px] font-bold text-slate-300">NO FOTO</span>'}</span>
                            <span class="text-xs text-slate-500">Foto #${photo.id}${photo.status !== 'active' ? ' · <span class="font-bold text-slate-400">' + escapeHtml(String(photo.status).toUpperCase()) + '</span>' : ''}</span>
                        </span>
                    </td>
                    <td class="px-3 py-2">${statusBadge(photo.status)}</td>
                    <td class="px-3 py-2 text-right whitespace-nowrap">
                        <button type="button" onclick="editCrud(${photo.id})" class="mr-3 font-bold text-[#B78A2D]">Edit</button>
                        <button type="button" onclick="deleteCrud(${photo.id})" class="font-bold text-red-600">Hapus</button>
                    </td>
                </tr>`;
            return groups.map(g => {
                const sorted = g.items.slice().sort((a, b) => (a.display_order || 0) - (b.display_order || 0));
                const first = sorted[0];
                const total = sorted.length;
                const key = galleryGroupKey(g.cat, g.status, first.id);
                const open = galleryExpandedGroups.has(key);
                const upCat = escapeHtml(galleryFolderLabel(g.cat));
                const title = g.status === 'draft' ? escapeHtml(first.caption || 'Foto tanpa caption') : upCat;
                const chevron = `<svg class="inline-block h-3 w-3 transition-transform ${open ? 'rotate-90' : ''}" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>`;
                return `<tr>
                    <td class="p-4">${first.image_url ? `<img src="${galleryImgSrc(first)}" alt="" class="mr-3 inline-block h-10 w-12 rounded object-cover align-middle">` : ''}<span class="align-middle"><b>${title}</b><br><span class="text-xs text-slate-500"><span class="inline-flex items-center gap-1 rounded bg-[#fff3e6] px-1.5 py-0.5 font-black uppercase tracking-wider text-[#c2410c]">${upCat}</span>${total > 1 ? ` · <span class="text-slate-500">${total} foto</span>` : ''}</span></span></td>
                    <td class="p-4 align-top">${statusBadge(first.status)}${total > 1 ? `<br><span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-[#111111] px-2 py-0.5 text-[10px] font-black text-white" title="Jumlah foto untuk “${title}”">🖼 ${total} foto</span>` : ''}</td>
                    <td class="p-4 text-right whitespace-nowrap">
                        <button type="button" onclick="editCrud(${first.id})" class="mr-3 font-bold text-[#B78A2D]">Edit</button>
                        <button type="button" onclick="deleteCrud(${first.id})" class="mr-3 font-bold text-red-600">Hapus</button>
                        ${total > 1 ? `<button type="button" onclick="galleryToggleGroup('${escapeHtml(key)}')" class="inline-flex items-center gap-1 font-bold text-[#111111] hover:text-[#FF5E00]" title="Tampilkan/sembunyikan ${total} foto">${chevron}<span class="text-[10px] uppercase tracking-wider">${open ? 'Tutup' : total + ' Foto'}</span></button>` : ''}
                    </td>
                </tr>${open ? `<tr class="gallery-photo-sub"><td colspan="3" class="p-0"><table class="w-full text-sm"><tbody>${sorted.map(renderItemRow).join('')}</tbody></table></td></tr>` : ''}`;
            }).join('');
        }
        function galleryToggleGroup(key) {
            if (galleryExpandedGroups.has(key)) galleryExpandedGroups.delete(key);
            else galleryExpandedGroups.add(key);
            const area = $('crud-list-area');
            if (area) { area.outerHTML = crudListAreaHtml(); bindCrudListEvents(); }
        }
        function crudRowHtml(x) {
            const mediaUrl = x.image_url || x.photo_url || '';
            const thumb = (currentModule === 'gallery' || currentModule === 'credentials' || currentModule === 'certifications' || currentModule === 'testimonials') && mediaUrl
                ? `<img src="${galleryImgSrc(x)}" alt="" class="mr-3 inline-block h-10 w-12 rounded object-cover align-middle">`
                : '';
            const sub = currentModule === 'gallery'
                ? `<span class="inline-flex items-center gap-1 rounded bg-[#fff3e6] px-1.5 py-0.5 font-black uppercase tracking-wider text-[#c2410c]">${escapeHtml(String(x.category || '').toUpperCase())}</span>`
                : escapeHtml(x.relationship || x.category || x.subtitle || x.role || '');
            return `<tr>
                <td class="p-4">${thumb}<span class="align-middle"><b>${escapeHtml(x.org_name || x.title || x.customer_name)}</b><br><span class="text-xs text-slate-500">${sub}</span></span></td>
                <td class="p-4">${statusBadge(x.status)}</td>
                <td class="p-4 text-right">
                    <button type="button" data-crud-edit="${x.id}" class="mr-3 font-bold text-[#B78A2D]">Edit</button>
                    <button type="button" data-crud-delete="${x.id}" class="font-bold text-red-600">Hapus</button>
                </td>
            </tr>`;
        }
        function crudListRowsHtml() {
            const rows = crudFilteredRows();
            if (!rows.length) {
                const emptyMsg = (currentModule === 'gallery' && crudGalleryFilter)
                    ? `Belum ada foto pada folder <b>${escapeHtml(String(crudGalleryFilter).toUpperCase())}</b>. Gunakan form di samping untuk menambah foto ke folder ini.`
                    : 'Belum ada data.';
                return `<tr><td colspan="3" class="p-8 text-center text-slate-500">${emptyMsg}</td></tr>`;
            }
            if (currentModule === 'gallery') return galleryGroupRows(rows);
            return rows.map(crudRowHtml).join('');
        }
        function crudListAreaHtml() {
            return `<div id="crud-list-area" class="overflow-hidden rounded-[26px] border border-[#E8E1D5] bg-white shadow-sm h-fit">
                ${currentModule === 'gallery' ? galleryFolderManagerHtml() + galleryFolderChipsHtml() : ''}
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[420px] text-sm text-left">
                        <thead class="bg-[#FBFAF7] text-[10px] font-black uppercase tracking-wider text-[#6A5B3A]">
                            <tr><th class="p-4">Konten</th><th class="p-4">Status</th><th class="p-4 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8E1D5]">
                            ${crudListRowsHtml()}
                        </tbody>
                    </table>
                </div>
            </div>`;
        }
        async function galleryFolderApi(action, payload) {
            const res = await fetch(`../api/modules.php?module=gallery&action=${action}`, {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message || 'Operasi folder gagal.');
            return data;
        }
        async function galleryFolderActionError(error) { notifyToast(error.message || 'Operasi folder gagal.', 'error'); }
        async function saveGalleryFolderCover(select) {
            const folder = (galleryFolders || []).find(item => String(item.category) === String(select.dataset.folderCover));
            if (!folder?.id) return;
            select.disabled = true;
            try {
                const result = await galleryFolderApi('folder_save', { id: folder.id, category: folder.category, description: folder.description || '', cover_image_url: select.value });
                notifyToast(result.message || 'Foto sampul berhasil diperbarui.', 'success');
            } catch (error) {
                notifyToast(error.message || 'Gagal mengubah foto sampul.', 'error');
            }
            await loadCrudModule('gallery');
        }
        document.addEventListener('change', event => {
            const select = event.target.closest?.('[data-folder-cover]');
            if (select) saveGalleryFolderCover(select);
        });
        async function addGalleryFolder() {
            const input = $('gallery-folder-new-name');
            if (!input) return;
            const name = String(input.value || '').trim();
            const description = String($('gallery-folder-new-description')?.value || '').trim();
            if (!name) { notifyToast('Ketik nama folder baru terlebih dahulu.', 'error'); return; }
            try {
                const data = await galleryFolderApi('folder_save', { category: name, description });
                notifyToast(data.message || 'Folder berhasil ditambahkan.', 'success');
                await loadCrudModule('gallery');
            } catch (error) { notifyToast(error.message, 'error'); }
        }
        async function renameGalleryFolder(oldCat) {
            const input = $('gallery-folder-rename-input');
            const newName = input ? String(input.value || '').trim() : '';
            const description = String($('gallery-folder-description-input')?.value || '').trim();
            if (!newName) { notifyToast('Ketik nama folder baru terlebih dahulu.', 'error'); return; }
            const folder = (galleryFolders || []).find(f => String(f.category) === String(oldCat));
            try {
                const data = await galleryFolderApi('folder_save', { id: folder && folder.id ? folder.id : 0, old_category: oldCat, category: newName, description });
                if (crudGalleryFilter && String(crudGalleryFilter) === String(oldCat)) crudGalleryFilter = data.category || oldCat;
                galleryFolderRenameCat = null;
                notifyToast(data.message || 'Nama folder berhasil diganti.', 'success');
                await loadCrudModule('gallery');
            } catch (error) { notifyToast(error.message, 'error'); }
        }
        async function deleteGalleryFolder(cat) {
            const ok = await notifyConfirm({ title: 'Hapus Folder?', message: `Hapus folder “${String(cat).toUpperCase()}” beserta seluruh foto di dalamnya? Tindakan tidak dapat dibatalkan.`, confirmText: 'Ya, Hapus', cancelText: 'Batal', danger: true });
            if (!ok) return;
            try {
                const data = await galleryFolderApi('folder_delete', { category: cat });
                if (crudGalleryFilter && String(crudGalleryFilter) === String(cat)) crudGalleryFilter = '';
                notifyToast(data.message || 'Folder beserta isinya berhasil dihapus.', 'success');
                await loadCrudModule('gallery');
            } catch (error) { notifyToast(error.message, 'error'); }
        }
        async function bindCrudListEvents() {
            // Bind item actions after every CRUD list render. Do not depend on
            // inline handlers, which can be unavailable in stricter browsers.
            document.querySelectorAll('[data-crud-edit]').forEach(btn => {
                btn.addEventListener('click', () => editCrud(btn.dataset.crudEdit));
            });
            document.querySelectorAll('[data-crud-delete]').forEach(btn => {
                btn.addEventListener('click', () => deleteCrud(btn.dataset.crudDelete));
            });
            // Folder chips: quick per-folder filter.
            document.querySelectorAll('.gallery-folder-chip').forEach(btn => {
                btn.addEventListener('click', () => {
                    crudGalleryFilter = btn.dataset.folder || '';
                    galleryExpandedGroups.clear();
                    const area = $('crud-list-area');
                    if (area) { area.outerHTML = crudListAreaHtml(); bindCrudListEvents(); }
                    const sel = $('gallery-category-select');
                    if (sel && crudGalleryFilter) { const opts = Array.from(sel.options); const hit = opts.find(o => o.value === crudGalleryFilter); if (hit) { sel.value = hit.value; } }
                });
            });
            // Folder manager buttons (add / rename / delete).
            const detailsEl = document.querySelector('details.group');
            if (detailsEl) detailsEl.addEventListener('toggle', () => {
                const toggleLabel = $('gallery-folder-manager-toggle');
                if (toggleLabel) toggleLabel.textContent = detailsEl.open ? 'sembunyikan ▲' : 'buka ▼';
            });
            const addBtn = document.querySelector('[data-folder-add]');
            if (addBtn) addBtn.addEventListener('click', addGalleryFolder);
            // Klik nama folder = buka isi folder (filter foto folder itu).
            document.querySelectorAll('[data-folder-open]').forEach(btn => {
                btn.addEventListener('click', () => {
                    crudGalleryFilter = btn.dataset.folderOpen || '';
                    galleryExpandedGroups.clear();
                    const area = $('crud-list-area');
                    if (area) { area.outerHTML = crudListAreaHtml(); bindCrudListEvents(); }
                    const sel = $('gallery-category-select');
                    if (sel && crudGalleryFilter) { const opts = Array.from(sel.options); const hit = opts.find(o => o.value === crudGalleryFilter); if (hit) { sel.value = hit.value; } }
                });
            });
            document.querySelectorAll('[data-folder-rename]').forEach(btn => {
                btn.addEventListener('click', () => { galleryFolderRenameCat = btn.dataset.folderRename; const area = $('crud-list-area'); if (area) { area.outerHTML = crudListAreaHtml(); bindCrudListEvents(); const input = $('gallery-folder-rename-input'); if (input) input.focus(); } });
            });
            document.querySelectorAll('[data-folder-rename-cancel]').forEach(btn => {
                btn.addEventListener('click', () => { galleryFolderRenameCat = null; const area = $('crud-list-area'); if (area) { area.outerHTML = crudListAreaHtml(); bindCrudListEvents(); } });
            });
            document.querySelectorAll('[data-folder-rename-save]').forEach(btn => {
                btn.addEventListener('click', () => { const input = $('gallery-folder-rename-input'); renameGalleryFolder(input ? input.dataset.oldCategory : ''); });
            });
            document.querySelectorAll('[data-folder-delete]').forEach(btn => {
                btn.addEventListener('click', () => deleteGalleryFolder(btn.dataset.folderDelete));
            });
        }
        function renderCrudApp(editItem = {}) {
            const isEdit = !!editItem.id;
            const crudApp = $('crud-app');
            if (crudApp) crudApp.classList.toggle('testimonials-layout', currentModule === 'testimonials');
            const supportsMediaUpload = ['gallery', 'testimonials'].includes(currentModule);
            const mediaField = currentModule === 'testimonials' ? 'photo_url' : 'image_url';
            const mediaUrl = String(editItem[mediaField] || '');
            const uploadLabel = currentModule === 'testimonials' ? 'Foto testimonial'
                : 'Upload gambar dari perangkat';
            const uploadBlock = supportsMediaUpload ? `<div class="rounded-xl border border-dashed border-[#D8C9A0] bg-[#fffaf0] p-4">
                <label class="block text-sm font-bold">${uploadLabel}</label>
                ${currentModule === 'testimonials' ? '<label class="mt-3 block text-[10px] font-black uppercase tracking-[.18em] text-[#6A5B3A]" for="testimonial-photo-source">Sumber foto</label><select id="testimonial-photo-source" class="mt-1 w-full rounded-lg border border-[#E3DCCB] bg-white px-3 py-2 text-sm"><option value="file">Pilih berkas dari browser / perangkat</option><option value="camera">Ambil dari kamera perangkat</option></select>' : ''}
                <input id="gallery-image-file" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm">
                <p class="mt-2 text-xs text-slate-500">JPG, PNG, atau WEBP; maksimal 5 MB. Upload akan mengisi URL foto otomatis.</p>
                <p id="gallery-upload-message" class="mt-2 text-sm"></p>
                ${mediaUrl ? `<img id="gallery-image-preview" src="${escapeHtml(mediaUrl.startsWith('http') ? mediaUrl : '../' + mediaUrl)}" alt="Preview gambar" class="mt-3 h-28 w-full rounded-lg object-cover">` : '<img id="gallery-image-preview" alt="Preview gambar" class="mt-3 hidden h-28 w-full rounded-lg object-cover">'}
            </div>` : '';
            $('crud-app').innerHTML = `
                <div class="crud-editor-column">
                <form id="crud-form" class="rounded-[26px] border border-[#E8E1D5] bg-white p-5 sm:p-7 shadow-sm h-fit">
                    <h3 class="text-xl font-black uppercase tracking-tight mb-5">${isEdit ? 'Edit' : 'Tambah'} ${moduleLabels[currentModule]}</h3>
                    <input type="hidden" name="id" value="${editItem.id || ''}">
                    <div class="space-y-4">
                        ${crudFields[currentModule].map(f => `<div>${renderCrudInput(f, editItem[f[0]])}</div>`).join('')}
                        ${uploadBlock}
                        ${currentModule === 'services' ? renderServicesCaseBlock(editItem) : ''}
                    </div>
                    <div class="mt-6 flex items-center gap-3">
                        <button class="rounded-xl bg-[#111111] px-4 py-3 font-bold text-white hover:bg-[#B78A2D] transition">Simpan</button>
                        <button type="button" id="crud-cancel" class="rounded-xl border border-[#E3DCCB] px-4 py-3 font-bold text-slate-600">Batal</button>
                    </div>
                    <p id="crud-notice" class="mt-3 text-sm"></p>
                </form>
                </div>
                <div class="crud-data-column">
                    ${crudListAreaHtml()}
                </div>
            `;
            enforceCrudSideBySide();
            $('crud-cancel').onclick = () => { if (currentModule === 'gallery' && !editItem.id) crudGalleryFilter = ''; renderCrudApp(); };
            $('crud-form').onsubmit = saveCrud;
            const fileInput = $('gallery-image-file');
            if (fileInput) fileInput.onchange = uploadGalleryImage;
            const testimonialSource = $('testimonial-photo-source');
            if (testimonialSource && fileInput) testimonialSource.onchange = () => {
                if (testimonialSource.value === 'camera') fileInput.setAttribute('capture', 'user');
                else fileInput.removeAttribute('capture');
            };
            wireGalleryNewFolderField();
            bindCrudListEvents();
            // Saat menambah foto baru pada folder aktif (filter), langsung pilih folder itu di dropdown.
            if (currentModule === 'gallery' && !isEdit && crudGalleryFilter) {
                const catSel = $('gallery-category-select');
                if (catSel) { const hit = Array.from(catSel.options).find(o => o.value === crudGalleryFilter); if (hit) catSel.value = hit.value; }
            }
        }

        async function loadCrudModule(mod) {
            currentModule = mod;
            galleryExpandedGroups.clear();
            if (mod !== 'gallery') crudGalleryFilter = '';
            const res = await fetch(`../api/modules.php?module=${mod}&action=list&status=all`);
            const data = await res.json();
            crudItems = data.data || [];
            // Muat registry folder galeri (id + kategori) agar panel "Kelola Folder" tersedia.
            if (mod === 'gallery') {
                try {
                    const folderRes = await fetch('../api/modules.php?module=gallery&action=folder_list', { headers: { 'X-CSRF-Token': csrfToken } });
                    const folderData = await folderRes.json();
                    galleryFolders = (folderData.data || []).map(f => ({ id: f.id, category: f.category, description: f.description || '', cover_image_url: f.cover_image_url || '' }));
                } catch (error) { galleryFolders = []; }
            }
            renderCrudApp();
        }

        function editCrud(id) { renderCrudApp(crudItems.find(x => x.id == id)); }
        
        async function deleteCrud(id) {
            const ok = await notifyConfirm({ title: 'Hapus Konten', message: 'Hapus konten ini? Tindakan tidak dapat dibatalkan.', confirmText: 'Ya, Hapus', cancelText: 'Batal', danger: true });
            if (!ok) return;
            const res = await fetch(`../api/modules.php?module=${currentModule}&action=delete`, {
                method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
                body: JSON.stringify({id})
            });
            const data = await res.json();
            if (data.status === 'success') { notifyToast(data.message || 'Data berhasil dihapus.', 'success'); loadCrudModule(currentModule); }
            else notifyToast(data.message || 'Gagal menghapus data.', 'error');
        }

        async function saveCrud(ev) {
            ev.preventDefault();
            const formData = new FormData($('crud-form'));
            const payload = Object.fromEntries(formData);
            // Gallery: folder baru yang diketik di form menggantikan pilihan dropdown.
            if (currentModule === 'gallery') {
                const newFolderInput = $('gallery-category-new');
                if (newFolderInput && !newFolderInput.classList.contains('hidden') && String(newFolderInput.value || '').trim()) {
                    payload.category = String(newFolderInput.value).trim();
                }
                payload.category = String(payload.category || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                payload.title = String(payload.caption || '').trim();
            }
            if (currentModule === 'services') {
                // Build the JSON the public site expects from the structured editor.
                const caseStudy = {
                    objective: String(payload.cs_objective || '').trim(),
                    technology: String(payload.cs_technology || '').trim(),
                    workflow: String(payload.cs_workflow || '').trim(),
                    results: String(payload.cs_results || '').trim()
                };
                const metrics = Array.from(document.querySelectorAll('.metric-row')).map(row => ({
                    label: (row.querySelector('.metric-label')?.value || '').trim(),
                    value: (row.querySelector('.metric-value')?.value || '').trim()
                })).filter(m => m.label || m.value);
                payload.full_case_study = JSON.stringify(caseStudy);
                payload.metrics = JSON.stringify(metrics);
                delete payload.cs_objective; delete payload.cs_technology; delete payload.cs_workflow; delete payload.cs_results;
            }
            const res = await fetch(`../api/modules.php?module=${currentModule}&action=save`, {
                method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            const notice = $('crud-notice');
            if (notice) notifyInline(notice, data.message, data.status === 'success' ? 'success' : 'error');
            if (data.status === 'success') { notifyToast(data.message || 'Data berhasil disimpan.', 'success'); loadCrudModule(currentModule); }
            else notifyToast(data.message || 'Gagal menyimpan data.', 'error');
        }

        async function uploadGalleryImage(event) {
            const file = event.target.files[0];
            const message = $('gallery-upload-message');
            if (!file) return;
            if (!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
                if (message) notifyInline(message, 'Pilih JPG, PNG, atau WEBP dengan ukuran maksimal 5 MB.', 'error');
                event.target.value = '';
                return;
            }
            if (message) notifyInline(message, 'Mengupload gambar…', 'info');
            const body = new FormData(); body.append('image', file);
            try {
                const res = await fetch(`../api/modules.php?module=${currentModule}&action=upload`, {method:'POST', headers:{'X-CSRF-Token':csrfToken}, body});
                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message || 'Upload gagal.');
                const mediaField = currentModule === 'testimonials' ? 'photo_url' : 'image_url';
                const mediaInput = document.querySelector(`[name="${mediaField}"]`);
                if (mediaInput) mediaInput.value = data.image_url;
                const preview = $('gallery-image-preview');
                preview.src = '../' + data.image_url; preview.classList.remove('hidden');
                const okText = currentModule === 'credentials' ? 'Logo berhasil diupload. Klik Simpan untuk menambahkan.'
                    : currentModule === 'testimonials' ? 'Foto berhasil diupload. Klik Simpan untuk menerapkan testimonial.'
                    : 'Gambar berhasil diupload. Klik Simpan untuk menambahkan ke galeri.';
                notifyToast(currentModule === 'credentials' ? 'Logo berhasil diupload.' : currentModule === 'testimonials' ? 'Foto testimonial berhasil diupload.' : 'Gambar berhasil diupload.', 'success');
                if (message) notifyInline(message, okText, 'success');
            } catch (error) { if (message) notifyInline(message, error.message || 'Upload gagal.', 'error'); notifyToast(error.message || 'Upload gagal.', 'error'); }
        }

        // 3. HERO BERANDA
        const heroDefaults = {
            'hero-headline': 'INDONESIA\nDRONE\nTRAINING\nCENTER',
            'hero-subtitle': 'Professional Drone Training for the Future.',
            'hero-desc': 'Layanan pelatihan drone profesional berfokus pada keterampilan terbang, pengoperasian kamera, dan keselamatan penerbangan langsung di lapangan.',
            'hero-cta-primary': 'Explore Training',
            'hero-cta-primary-link': '#tab-content-training',
            'hero-cta-secondary': 'Contact IDTC',
            'hero-cta-secondary-link': '',
            'hero-background': ''
        };
        const heroFields = {
            'hero-headline': 'hero-headline-input', 'hero-subtitle': 'hero-subtitle-input', 'hero-desc': 'hero-desc-input',
            'hero-cta-primary': 'hero-primary-label-input', 'hero-cta-primary-link': 'hero-primary-link-input',
            'hero-cta-secondary': 'hero-secondary-label-input', 'hero-cta-secondary-link': 'hero-secondary-link-input',
            'hero-background': 'hero-background-input'
        };
        let heroItems = [];
        function setHeroPreview(url) {
            const preview = $('hero-image-preview');
            if (!preview) return;
            const value = String(url || '').trim();
            if (!value) { preview.classList.add('hidden'); preview.removeAttribute('src'); return; }
            preview.src = /^(https?:)?\/\//i.test(value) ? value : '../' + value.replace(/^\.\//, '');
            preview.classList.remove('hidden');
        }
        async function loadHero() {
            try {
                const response = await fetch('../api/content.php?action=list');
                const data = await response.json();
                heroItems = (data.data || []).filter(item => Object.prototype.hasOwnProperty.call(heroFields, item.content_key));
                Object.entries(heroFields).forEach(([key, id]) => {
                    const row = heroItems.find(item => item.content_key === key);
                    $(id).value = row ? row.content_value : heroDefaults[key];
                });
                setHeroPreview($('hero-background-input').value);
            } catch (error) { notifyInline($('hero-message'), 'Gagal memuat data Hero.', 'error'); }
        }
        async function uploadHeroImage(event) {
            const file = event.target.files?.[0];
            const message = $('hero-upload-message');
            event.target.value = '';
            if (!file) return;
            if (!/^image\/(jpeg|png|webp)$/i.test(file.type)) { notifyInline(message, 'Pilih gambar JPG, PNG, atau WEBP.', 'error'); return; }
            if (file.size > 5 * 1024 * 1024) { notifyInline(message, 'Ukuran gambar maksimal 5 MB.', 'error'); return; }
            const body = new FormData(); body.append('image', file);
            notifyInline(message, 'Mengunggah gambar…', 'info');
            try {
                const response = await fetch('../api/content.php?action=upload_hero_image', {method: 'POST', headers: {'X-CSRF-Token': csrfToken}, body});
                const data = await response.json();
                if (data.status !== 'success') throw new Error(data.message || 'Gagal mengunggah gambar.');
                $('hero-background-input').value = data.image_url;
                setHeroPreview(data.image_url);
                notifyInline(message, data.message, 'success');
            } catch (error) { notifyInline(message, error.message || 'Gagal mengunggah gambar.', 'error'); }
        }
        async function saveHero(event) {
            event.preventDefault();
            const message = $('hero-message');
            const entries = Object.entries(heroFields).map(([key, id]) => ({ key, value: $(id).value.trim() }));
            if (entries.some(entry => !entry.value && entry.key !== 'hero-background')) { notifyInline(message, 'Semua isian selain gambar latar wajib diisi.', 'error'); return; }
            try {
                for (const entry of entries) {
                    const existing = heroItems.find(item => item.content_key === entry.key);
                    if (!entry.value) { if (existing) await fetch('../api/content.php?action=delete', { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken}, body: JSON.stringify({id: existing.id}) }); continue; }
                    const response = await fetch('../api/content.php?action=save', { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken}, body: JSON.stringify({id: existing?.id || '', section_name: 'Hero Beranda', content_key: entry.key, content_value: entry.value}) });
                    const data = await response.json();
                    if (data.status !== 'success') throw new Error(data.message || 'Gagal menyimpan Hero.');
                }
                notifyInline(message, 'Hero berhasil disimpan dan akan tampil di beranda.', 'success');
                notifyToast('Hero beranda berhasil diperbarui.', 'success');
                loadHero();
            } catch (error) { notifyInline(message, error.message || 'Gagal menyimpan Hero.', 'error'); }
        }
        async function resetHero() {
            const ok = await notifyConfirm({title: 'Kembalikan Hero Default', message: 'Semua perubahan Hero dari admin akan dihapus dan beranda kembali ke tampilan bawaan.', confirmText: 'Kembalikan', cancelText: 'Batal', danger: true});
            if (!ok) return;
            try {
                for (const item of heroItems) await fetch('../api/content.php?action=delete', {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken}, body: JSON.stringify({id: item.id})});
                notifyToast('Hero kembali ke tampilan bawaan.', 'success');
                loadHero();
            } catch (error) { notifyInline($('hero-message'), 'Gagal mengembalikan Hero.', 'error'); }
        }
        $('hero-form').addEventListener('submit', saveHero);
        $('hero-reset').addEventListener('click', resetHero);
        $('hero-image-file').addEventListener('change', uploadHeroImage);
        $('hero-background-input').addEventListener('input', event => setHeroPreview(event.target.value));

        // 4. CMS CONTENT
        async function loadCms() { const response = await fetch('../api/content.php?action=list'); const data = await response.json(); cmsItems = data.data || []; $('cms-list').innerHTML = cmsItems.length ? cmsItems.map(item => `<tr><td class="p-4">${escapeHtml(item.section_name)}</td><td class="p-4 font-mono text-xs">${escapeHtml(item.content_key)}</td><td class="max-w-md truncate p-4">${escapeHtml(item.content_value)}</td><td class="p-4 text-right"><button onclick="editCms(${item.id})" class="mr-3 font-bold text-[#B78A2D]">Edit</button><button onclick="deleteCms(${item.id})" class="font-bold text-red-600">Hapus</button></td></tr>`).join('') : '<tr><td colspan="4" class="p-8 text-center text-slate-500">Belum ada konten CMS.</td></tr>'; }
        function resetCms(){ $('cms-form').reset(); $('cms-id').value=''; }
        function editCms(id){ const item=cmsItems.find(value=>value.id===id); if(!item)return; $('cms-id').value=item.id; $('cms-section').value=item.section_name; $('cms-key').value=item.content_key; $('cms-value').value=item.content_value; window.scrollTo({top: 0, behavior: 'smooth'}); }
        async function deleteCms(id){
            const ok = await notifyConfirm({ title: 'Hapus Konten CMS', message: 'Hapus konten ini? Perubahan akan langsung terlihat di website.', confirmText: 'Ya, Hapus', cancelText: 'Batal', danger: true });
            if (!ok) return;
            const response=await fetch('../api/content.php?action=delete',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({id})});
            const data=await response.json();
            if(data.status==='success'){ notifyToast(data.message || 'Konten berhasil dihapus.', 'success'); loadCms(); }
            else notifyToast(data.message || 'Gagal menghapus konten.', 'error');
        }
        $('cms-reset').addEventListener('click',resetCms); 
        $('cms-form').addEventListener('submit',async event=>{
            event.preventDefault();
            const cmsMsg = $('cms-message');
            const payload={id:$('cms-id').value,section_name:$('cms-section').value,content_key:$('cms-key').value.trim(),content_value:$('cms-value').value.trim()};
            if (!payload.content_key || !payload.content_value) { if (cmsMsg) notifyInline(cmsMsg, 'Key konten dan isi teks wajib diisi.', 'error'); return; }
            const existingItem = cmsItems.find(item => item.content_key === payload.content_key);
            if (!payload.id && existingItem) {
                const text = 'Konten dengan key ini sudah ada. Gunakan tombol Edit pada daftar konten untuk mengubahnya.';
                if (cmsMsg) notifyInline(cmsMsg, text, 'error');
                notifyToast(text, 'error');
                return;
            }
            try {
                const response=await fetch('../api/content.php?action=save',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify(payload)});
                const data=await response.json();
                if (data.status !== 'success') throw new Error(data.message || 'Gagal menyimpan konten.');
                if(cmsMsg)notifyInline(cmsMsg,data.message || 'Konten berhasil disimpan.','success');
                notifyToast(data.message || 'Konten berhasil disimpan.','success');
                resetCms();loadCms();
            } catch (error) {
                const message = error.message || 'Gagal menyimpan konten.';
                if (cmsMsg) notifyInline(cmsMsg, message, 'error');
                notifyToast(message, 'error');
            }
        });

        // 5. FIND US (KONTAK)
        async function loadFindUs() {
            const msg = $('findus-message');
            if (msg) notifyInline(msg, '');
            let rows = [];
            try {
                const response = await fetch('../api/content.php?action=list');
                const data = await response.json();
                if (data.status === 'success') rows = (data.data || []).filter(r => findUsKeys.includes(r.content_key));
            } catch (error) {}
            findUsRows = rows;
            findUsKeys.forEach(key => {
                const field = $(findUsFields[key]);
                if (field) field.value = (rows.find(r => r.content_key === key) || {}).content_value || '';
            });
        }
        $('findus-form').addEventListener('submit', async event => {
            event.preventDefault();
            const msg = $('findus-message');
            const entries = findUsKeys.map(key => ({
                id: (findUsRows.find(r => r.content_key === key) || {}).id || '',
                section_name: 'Find Us',
                content_key: key,
                content_value: ($(findUsFields[key]) ? $(findUsFields[key]).value.trim() : '')
            }));
            if (entries.some(e => !e.content_value)) {
                if (msg) notifyInline(msg, 'Semua field kontak wajib diisi.', 'error');
                return;
            }
            try {
                for (const entry of entries) {
                    const res = await fetch('../api/content.php?action=save', {
                        method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
                        body: JSON.stringify(entry)
                    });
                    const data = await res.json();
                    if (data.status !== 'success') throw new Error(data.message || 'Gagal menyimpan kontak.');
                }
                if (msg) notifyInline(msg, 'Kontak berhasil disimpan dan langsung tampil di website.', 'success');
                notifyToast('Kontak berhasil disimpan.', 'success');
                loadFindUs();
            } catch (error) {
                const text = error.message || 'Gagal menyimpan kontak.';
                if (msg) notifyInline(msg, text, 'error');
                notifyToast(text, 'error');
            }
        });

        // 6. PROFILE (AKUN ADMIN) — camera capture & upload
        let profileStream = null;
        let profileFacing = 'environment';
        let pendingProfileBlob = null;
        const profileVideo = $('profile-camera-video');
        function showProfileMessage(text, isError, type) {
            const msg = $('profile-message');
            if (!msg) return;
            notifyInline(msg, text, type || (isError ? 'error' : 'success'));
        }
        function closeProfileCamera() {
            if (profileStream) { profileStream.getTracks().forEach(t => t.stop()); profileStream = null; }
            if (profileVideo) profileVideo.srcObject = null;
            const modal = $('profile-camera-modal');
            if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
        }
        async function openProfileCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { showProfileMessage('Kamera tidak didukung browser ini.', true); return; }
            try {
                if (profileStream) profileStream.getTracks().forEach(t => t.stop());
                profileStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: profileFacing } }, audio: false });
            } catch (error) {
                try { profileStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false }); }
                catch (error2) { showProfileMessage('Kamera tidak dapat diakses. Periksa izin kamera (HTTPS / localhost).', true); return; }
            }
            if (profileVideo) { profileVideo.srcObject = profileStream; await profileVideo.play().catch(() => {}); }
            const modal = $('profile-camera-modal');
            if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
        }
        function drawProfileCover(source, max = 900, type = 'image/jpeg', quality = 0.87) {
            return new Promise(resolve => {
                const width = source.videoWidth || source.naturalWidth || 0;
                const height = source.videoHeight || source.naturalHeight || 0;
                if (!width || !height) return resolve(null);
                const size = Math.min(max, Math.min(width, height));
                const canvas = document.createElement('canvas');
                canvas.width = size;
                canvas.height = size;
                const ctx = canvas.getContext('2d');
                // Browser camera feeds are commonly mirrored for front cameras.
                // Flip once here so the saved image matches the non-mirrored preview.
                ctx.translate(size, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(source, (width - size) / 2, (height - size) / 2, size, size, 0, 0, size, size);
                canvas.toBlob(blob => resolve(blob), type, quality);
            });
        }
        async function captureProfilePhoto() {
            if (!profileVideo || !profileVideo.videoWidth) return;
            const blob = await drawProfileCover(profileVideo);
            closeProfileCamera();
            if (blob) setPendingProfilePhoto(blob);
        }
        function setPendingProfilePhoto(blob) {
            pendingProfileBlob = blob;
            const box = $('profile-confirm-box');
            const preview = $('profile-confirm-image');
            if (preview) { if (preview.src && preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src); preview.src = URL.createObjectURL(blob); }
            if (box) box.classList.remove('hidden');
            showProfileMessage('Foto siap. Tekan “Simpan Foto” untuk menyimpan, atau “Batal” untuk membatalkan.', false, 'info');
            const boxTop = box && box.getBoundingClientRect ? box.getBoundingClientRect().top + window.pageYOffset - 90 : 0;
            if (boxTop) window.scrollTo({ top: boxTop, behavior: 'smooth' });
        }
        function cancelPendingProfilePhoto() {
            pendingProfileBlob = null;
            const box = $('profile-confirm-box');
            const preview = $('profile-confirm-image');
            if (preview) { if (preview.src && preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src); preview.src = ''; }
            if (box) box.classList.add('hidden');
            showProfileMessage('Pengambilan/unggahan foto dibatalkan.', false, 'info');
        }
        function confirmPendingProfilePhoto() {
            if (!pendingProfileBlob) return;
            const blob = pendingProfileBlob;
            pendingProfileBlob = null;
            uploadProfilePhoto(blob);
        }
        function uploadProfilePhoto(blob) {
            const confirmBox = $('profile-confirm-box');
            const confirmImg = $('profile-confirm-image');
            if (confirmBox) confirmBox.classList.add('hidden');
            if (confirmImg) { if (confirmImg.src && confirmImg.src.startsWith('blob:')) URL.revokeObjectURL(confirmImg.src); confirmImg.src = ''; }
            showProfileMessage('Mengunggah foto…', false, 'info');
            const body = new FormData();
            body.append('photo', blob, 'profile-photo.jpg');
            fetch('../api/profile.php?action=save_photo', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body })
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message || 'Gagal menyimpan foto.');
                    const preview = $('profile-avatar-preview');
                    const initial = $('profile-avatar-initial');
                    if (preview) { preview.src = '../' + data.photo; preview.classList.remove('hidden'); }
                    if (initial) initial.classList.add('hidden');
                    const removeBtn = $('profile-remove-photo');
                    if (removeBtn) removeBtn.classList.remove('hidden');
                    showProfileMessage('Foto profil berhasil disimpan. Halaman akan dimuat ulang…', false, 'success');
                    setTimeout(() => location.reload(), 900);
                })
                .catch(err => showProfileMessage(err.message || 'Gagal menyimpan foto.', true));
        }
        function wireProfile() {
            document.querySelectorAll('.password-visibility-toggle').forEach(button => {
                button.addEventListener('click', () => {
                    const input = $(button.dataset.passwordTarget);
                    if (!input) return;
                    const isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';
                    button.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
                    button.setAttribute('title', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
                    button.setAttribute('aria-pressed', String(isHidden));
                });
            });
            const openCamera = $('profile-open-camera');
            if (openCamera) openCamera.addEventListener('click', openProfileCamera);
            const flip = $('profile-camera-flip');
            if (flip) flip.addEventListener('click', () => { profileFacing = profileFacing === 'environment' ? 'user' : 'environment'; openProfileCamera(); });
            const capture = $('profile-camera-capture');
            if (capture) capture.addEventListener('click', captureProfilePhoto);
            const closeBtn = $('profile-camera-close');
            const cancelBtn = $('profile-camera-cancel');
            if (closeBtn) closeBtn.addEventListener('click', closeProfileCamera);
            if (cancelBtn) cancelBtn.addEventListener('click', closeProfileCamera);
            const fileInput = $('profile-file-input');
            const openUpload = $('profile-open-upload');
            if (openUpload) openUpload.addEventListener('click', () => { if (fileInput) fileInput.click(); });
            if (fileInput) fileInput.addEventListener('change', async () => {
                const file = fileInput.files && fileInput.files[0];
                fileInput.value = '';
                if (!file) return;
                if (!/^image\/(jpeg|png|webp)$/i.test(file.type)) { showProfileMessage('Pilih file gambar JPG, PNG, atau WEBP.', true); return; }
                if (file.size > 8 * 1024 * 1024) { showProfileMessage('Ukuran gambar maksimal 8 MB.', true); return; }
                showProfileMessage('Memproses gambar…', false, 'info');
                const image = new Image();
                image.onload = async () => {
                    const type = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                    const blob = await drawProfileCover(image, 1000, type, 0.9);
                    if (blob) setPendingProfilePhoto(blob); else showProfileMessage('Gambar tidak dapat diproses.', true);
                };
                image.onerror = () => showProfileMessage('File gambar tidak valid.', true);
                image.src = URL.createObjectURL(file);
            });
            const confirmSave = $('profile-confirm-save');
            const confirmCancel = $('profile-confirm-cancel');
            if (confirmSave) confirmSave.addEventListener('click', confirmPendingProfilePhoto);
            if (confirmCancel) confirmCancel.addEventListener('click', cancelPendingProfilePhoto);
            const removeBtn = $('profile-remove-photo');
            if (removeBtn) removeBtn.addEventListener('click', async () => {
                const ok = await notifyConfirm({ title: 'Hapus Foto Profil?', message: 'Hapus foto profil ini? Foto akan kembali ke avatar inisial.', confirmText: 'Ya, Hapus', cancelText: 'Batal', danger: true });
                if (!ok) return;
                showProfileMessage('Menghapus foto…', false, 'info');
                try {
                    const res = await fetch('../api/profile.php?action=remove_photo', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken } });
                    const data = await res.json();
                    if (data.status !== 'success') throw new Error(data.message || 'Gagal menghapus foto.');
                    const preview = $('profile-avatar-preview');
                    const initial = $('profile-avatar-initial');
                    if (preview) preview.classList.add('hidden');
                    if (initial) initial.classList.remove('hidden');
                    if (removeBtn) removeBtn.classList.add('hidden');
                    showProfileMessage('Foto profil dihapus.', false, 'success');
                    notifyToast('Foto profil dihapus.', 'success');
                } catch (err) { const text = err.message || 'Gagal menghapus foto.'; showProfileMessage(text, true); notifyToast(text, 'error'); }
            });
            const passwordForm = $('profile-password-form');
            const verifyPasswordForm = $('profile-password-verify-form');
            const verifyPasswordButton = $('profile-password-verify');
            const passwordMessage = $('profile-password-message');
            const currentPasswordInput = $('profile-current-password');
            if (currentPasswordInput) currentPasswordInput.addEventListener('keydown', event => {
                if (event.key === 'Enter' && !currentPasswordInput.disabled) {
                    event.preventDefault();
                    verifyPasswordForm?.requestSubmit();
                }
            });
            ['profile-new-password', 'profile-confirm-password'].forEach(id => {
                $(id)?.addEventListener('keydown', event => {
                    if (event.key === 'Enter' && !passwordForm?.classList.contains('hidden')) {
                        event.preventDefault();
                        $('profile-password-submit')?.click();
                    }
                });
            });
            if (verifyPasswordForm) verifyPasswordForm.addEventListener('submit', async event => {
                event.preventDefault();
                const currentPassword = $('profile-current-password').value;
                if (!currentPassword) { notifyInline(passwordMessage, 'Masukkan password lama terlebih dahulu.', 'error'); return; }

                verifyPasswordButton.disabled = true;
                verifyPasswordButton.classList.add('opacity-60', 'cursor-not-allowed');
                try {
                    const response = await fetch('../api/profile.php?action=verify_current_password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                        body: JSON.stringify({ current_password: currentPassword })
                    });
                    const data = await response.json();
                    if (!response.ok || data.status !== 'success') throw new Error(data.message || 'Akses reset ditolak.');
                    $('profile-current-password').disabled = true;
                    verifyPasswordButton.classList.add('hidden');
                    passwordForm.classList.remove('hidden');
                    passwordForm.classList.add('grid');
                    notifyInline(passwordMessage, 'Password lama benar. Silakan buat password baru.', 'success');
                    $('profile-new-password').focus();
                } catch (err) {
                    notifyInline(passwordMessage, err.message || 'Akses reset ditolak.', 'error');
                } finally {
                    verifyPasswordButton.disabled = false;
                    verifyPasswordButton.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            });
            if (passwordForm) passwordForm.addEventListener('submit', async event => {
                event.preventDefault();
                const newPassword = $('profile-new-password').value;
                const confirmPassword = $('profile-confirm-password').value;
                const submit = $('profile-password-submit');

                if (newPassword.length < 8) { notifyInline(passwordMessage, 'Password baru minimal 8 karakter.', 'error'); return; }
                if (newPassword !== confirmPassword) { notifyInline(passwordMessage, 'Konfirmasi password baru tidak sama.', 'error'); return; }

                submit.disabled = true;
                submit.classList.add('opacity-60', 'cursor-not-allowed');
                try {
                    const response = await fetch('../api/profile.php?action=change_password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                        body: JSON.stringify({ new_password: newPassword, confirm_password: confirmPassword })
                    });
                    const data = await response.json();
                    if (!response.ok || data.status !== 'success') throw new Error(data.message || 'Gagal mengubah password.');
                    passwordForm.reset();
                    passwordForm.classList.add('hidden');
                    passwordForm.classList.remove('grid');
                    $('profile-current-password').value = '';
                    $('profile-current-password').disabled = false;
                    verifyPasswordButton.classList.remove('hidden');
                    notifyInline(passwordMessage, data.message || 'Password berhasil diperbarui.', 'success');
                    notifyToast('Password login berhasil diubah.', 'success');
                } catch (err) {
                    notifyInline(passwordMessage, err.message || 'Gagal mengubah password.', 'error');
                } finally {
                    submit.disabled = false;
                    submit.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            });
        }
        wireProfile();

        // Restore keyboard focus after navigating between the main admin page
        // and the separate Artikel page.
        try {
            const keyboardTarget = sessionStorage.getItem('idtcSidebarKeyboardTarget');
            if (keyboardTarget) {
                sessionStorage.removeItem('idtcSidebarKeyboardTarget');
                document.querySelector(`aside nav .nav-btn[data-target="${keyboardTarget}"]`)?.focus();
            }
        } catch (e) {}

        // Poll for login-page visits made from a different browser. This is a
        // dashboard alert, not a browser push notification.
        async function checkAdminSecurityAlerts() {
            try {
                const response = await fetch('../api/security.php', { credentials: 'same-origin' });
                const data = await response.json();
                if (data.status === 'success' && data.events?.length) {
                    const total = data.events.length;
                    notifyToast(`${total} akses ke halaman login admin terdeteksi dari browser lain. Periksa keamanan akun Anda.`, 'error');
                }
            } catch (error) { /* Avoid interrupting normal admin work. */ }
        }
        checkAdminSecurityAlerts();
        setInterval(checkAdminSecurityAlerts, 20000);

        // Init
        $('logout-button').addEventListener('click', async () => { await fetch('../api/auth.php?action=logout', {method:'POST',headers:{'X-CSRF-Token':csrfToken}}); location.reload(); }); 
        const initialModule = <?php echo json_encode($initialModule); ?>;
        const initialButton = document.querySelector(`.nav-btn[data-target="${initialModule}"]`);
        if (initialButton) initialButton.click(); else loadDashboard();
    </script>
    <!-- Jam Indonesia (WIB / WITA / WIT) -->
    <script src="../js/indonesia-clocks.js"></script>
<?php endif; ?>
</body>
</html>
