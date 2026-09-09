<?php
require_once __DIR__ . '/../../config.php';
$articleAdminName = $_SESSION['admin_username'] ?? 'Admin';
$articleAdminInitial = function_exists('mb_substr') ? mb_substr($articleAdminName, 0, 1) : substr($articleAdminName, 0, 1);
$articleAdminProfile = null;
try {
    $articleProfilePdo = getDbConnection();
    if ($articleProfilePdo) {
        $articleProfileStmt = $articleProfilePdo->prepare('SELECT photo FROM admin_users WHERE username = ?');
        $articleProfileStmt->execute([$articleAdminName]);
        $articleAdminProfile = $articleProfileStmt->fetch() ?: null;
    }
} catch (Throwable $e) { $articleAdminProfile = null; }

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || ($_SESSION['admin_auth_version'] ?? 0) !== 2) {
    header('Location: ../login.php?redirect=articles');
    exit;
}

/*
 * The article studio is rendered from the original CRUD markup. Keeping one
 * source of markup prevents the Admin page from drifting away from the CRUD
 * that was previously opened from the footer.
 */
$source = file_get_contents(__DIR__ . '/../../index.php');
$between = static function (string $from, string $to) use ($source): string {
    $start = strpos($source, $from);
    $end = $start === false ? false : strpos($source, $to, $start);
    return ($start === false || $end === false) ? '' : substr($source, $start, $end - $start);
};
$studioMarkup = $between('<!-- CONTENT STUDIO OVERLAY / PORTAL SHELL -->', '<!-- PUBLIC ARTICLE READER MODAL -->');
$articleModals = $between('<!-- ADMIN PREVIEW MODAL -->', '<div id="chatbot-widget">');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel | IDTC</title>
    <base href="../../">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin/admin-style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/notify.js"></script>
    <style>
        .nav-btn.active { background-color: rgba(255,255,255,0.1); border-left: 3px solid #FF5E00; }
        #admin-portal-view { position: static !important; display: block !important; overflow: visible !important; }
        .article-calendar-day { position:relative; min-height:3.75rem; border:1px solid #e8e1d5; border-radius:.65rem; background:#fff; padding:.55rem; text-align:left; font-size:.8rem; font-weight:800; color:#3f3f3f; transition:.2s; }
        .article-calendar-day:hover,.article-calendar-day:focus-visible { border-color:#ff5e00; background:#fffaf5; outline:0; transform:translateY(-1px); }
        .article-calendar-day.is-today { border-color:#ff5e00; box-shadow:inset 0 0 0 1px #ff5e00; }
        .article-calendar-day.has-events { background:#fff3e8; color:#111; }
        .article-calendar-day i { position:absolute; right:.4rem; bottom:.35rem; display:inline-flex; min-width:1.2rem; height:1.2rem; align-items:center; justify-content:center; border-radius:9999px; background:#ff5e00; color:#fff; font-size:.65rem; font-style:normal; }
        .article-calendar-agenda-item { display:flex; width:100%; flex-direction:column; gap:.2rem; border-left:3px solid #ff5e00; background:#fffaf5; padding:.65rem .75rem; text-align:left; transition:background-color .2s; }
        .article-calendar-agenda-item:hover,.article-calendar-agenda-item:focus-visible { background:#fff0e2; outline:0; }
        .article-calendar-agenda-item strong { overflow:hidden; color:#111; font-size:.75rem; line-height:1.35; text-overflow:ellipsis; white-space:nowrap; }
        .article-calendar-agenda-date,.article-calendar-agenda-item small { color:#8a7b58; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
        .article-calendar-agenda-item small.is-published { color:#047857; }
        @media (max-width: 767px) {
            #admin-portal-view .mx-auto { padding-left: 1rem; padding-right: 1rem; }
            #admin-portal-view .admin-panel-surface { border-radius: 1rem; }
            #admin-portal-view .admin-panel-surface > main { padding-left: 1rem; padding-right: 1rem; }
            #admin-portal-view .admin-panel-surface header { padding-left: 1rem; padding-right: 1rem; }
            #admin-portal-view input, #admin-portal-view select, #admin-portal-view textarea { font-size: 16px; }
        }
    </style>
</head>
<body class="min-h-screen bg-[#FDFDFB] font-sans text-[#111111]">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="hidden w-64 shrink-0 flex-col bg-[#111111] text-white md:sticky md:top-0 md:flex md:h-screen overflow-y-auto">
            <div class="border-b border-white/5 px-6 pt-6 pb-5">
                <h1 class="text-2xl font-black uppercase tracking-tight">Control Center</h1>
                <p class="mt-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#B78A2D]">Indonesia Drone Training Center</p>
            </div>

            <nav class="flex-1 space-y-1 px-4 py-4">
                <a href="admin/index.php" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
                <a href="admin/index.php?module=hero" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg><span>Hero Beranda</span></a>
                <a href="admin/index.php?module=training" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg><span>Training Programs</span></a>
                <a href="admin/index.php?module=services" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg><span>Services</span></a>
                <a href="admin/index.php?module=gallery" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg><span>Dokumentasi Laporan</span></a>
                <a href="admin/index.php?module=testimonials" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span>Testimonials</span></a>
                <a href="admin/index.php?module=credentials" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 7.7l5.4-.8z"/></svg><span>Credentials</span></a>
                <a href="admin/index.php?module=certifications" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.5 13l2 7-5.5-3-5.5 3 2-7"/></svg><span>Certifications</span></a>
                <a href="admin/articles/" aria-current="page" class="nav-btn active w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5"/></svg><span>Artikel</span></a>
                <a href="admin/index.php?module=findus" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><span>Find Us (Kontak)</span></a>
                <a href="admin/index.php?module=cms" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h10"/></svg><span>Site Content (CMS)</span></a>
                <a href="admin/index.php?module=profile" class="nav-btn w-full flex items-center gap-3 px-3.5 py-3 text-left text-xs font-black uppercase tracking-[0.14em] transition hover:bg-white/5"><svg class="nav-ic h-[18px] w-[18px] shrink-0 text-[#FF5E00]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>Profil Admin</span></a>
            </nav>

            <div class="border-t border-white/10 p-5">
                <div class="flex items-center gap-3">
                    <?php if (!empty($articleAdminProfile['photo'])): ?>
                    <img src="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim($articleAdminProfile['photo'], '/'), ENT_QUOTES, 'UTF-8'); ?>" alt="Foto profil" class="h-9 w-9 shrink-0 rounded-full border border-white/20 object-cover">
                    <?php else: ?>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#FF5E00] text-xs font-black uppercase text-white"><?php echo htmlspecialchars($articleAdminInitial, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-white"><?php echo htmlspecialchars($articleAdminName, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Administrator</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="min-w-0 flex-1">
            <div class="border-b border-[#E8E1D5] bg-white px-4 py-3 md:hidden">
                <select id="mobile-module-select" aria-label="Pilih menu" class="w-full rounded-xl border border-[#D8C9A0] bg-white px-3 py-2.5 text-sm font-bold uppercase tracking-wide text-[#111111]" onchange="if(this.value==='articles'){return;}location.href=this.value==='dashboard'?'admin/index.php':'admin/index.php?module='+this.value">
                    <option value="dashboard">Dashboard</option>
                    <option value="hero">Hero Beranda</option>
                    <option value="training">Training Programs</option>
                    <option value="services">Services</option>
                    <option value="gallery">Dokumentasi Laporan (Galeri)</option>
                    <option value="testimonials">Testimonials</option>
                    <option value="credentials">Credentials</option>
                    <option value="certifications">Certifications</option>
                    <option value="articles" selected>Artikel</option>
                    <option value="findus">Find Us (Kontak)</option>
                    <option value="cms">Site Content (CMS)</option>
                    <option value="profile">Profil Admin</option>
                </select>
            </div>
            <?php echo $studioMarkup; ?>
        </main>
    </div>
    <?php echo $articleModals; ?>
    <script>window.IDTC_ADMIN_LOGGED_IN = true;</script>
    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => loadAdminArticles('all'));
        document.addEventListener('DOMContentLoaded', () => {
            try {
                const target = sessionStorage.getItem('idtcSidebarKeyboardTarget');
                if (target !== 'articles') return;
                sessionStorage.removeItem('idtcSidebarKeyboardTarget');
                document.querySelector('aside nav a[aria-current="page"]')?.focus();
            } catch (error) {}
        });
        document.addEventListener('keydown', event => {
            if (!['ArrowUp', 'ArrowDown'].includes(event.key) || event.altKey || event.ctrlKey || event.metaKey) return;
            const activeElement = document.activeElement;
            if (activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName)) return;
            const sidebarItems = [...document.querySelectorAll('aside nav a')];
            if (!sidebarItems.length) return;
            event.preventDefault();
            const current = sidebarItems.indexOf(activeElement);
            const step = event.key === 'ArrowDown' ? 1 : -1;
            const next = current < 0 ? (step > 0 ? 0 : sidebarItems.length - 1) : (current + step + sidebarItems.length) % sidebarItems.length;
            const nextItem = sidebarItems[next];
            nextItem.focus();
            nextItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            try {
                const url = new URL(nextItem.href, window.location.href);
                const target = url.pathname.includes('/articles/') ? 'articles' : (url.searchParams.get('module') || 'dashboard');
                sessionStorage.setItem('idtcSidebarKeyboardTarget', target);
            } catch (error) {}
            nextItem.click();
        });
    </script>
</body>
</html>
