<?php
require_once __DIR__ . '/config.php';
header('Cache-Control: no-store, max-age=0, must-revalidate');
header('Pragma: no-cache');

$pdo = getDbConnection();
$dynamicServices = [];
$dynamicTraining = [];
$dynamicTestimonials = [];
$dynamicCredentials = [];
$dynamicCertifications = [];
$dynamicCms = [];

if ($pdo) {
    try {
        $stmtSvc = $pdo->query("SELECT * FROM services WHERE status = 'active' ORDER BY display_order ASC, id DESC");
        $dynamicServices = $stmtSvc->fetchAll(PDO::FETCH_ASSOC);

        $stmtTrain = $pdo->query("SELECT * FROM training_programs WHERE status = 'active' ORDER BY display_order ASC, id DESC");
        $dynamicTraining = $stmtTrain->fetchAll(PDO::FETCH_ASSOC);

        $stmtTesti = $pdo->query("SELECT * FROM testimonials WHERE status = 'active' ORDER BY display_order ASC, id DESC");
        $dynamicTestimonials = $stmtTesti->fetchAll(PDO::FETCH_ASSOC);

        $stmtCred = $pdo->query("SELECT * FROM credentials WHERE status = 'active' ORDER BY display_order ASC, id ASC");
        $dynamicCredentials = $stmtCred->fetchAll(PDO::FETCH_ASSOC);

        // Certification cards (CERTIFICATION section). Only seed a brand-new
        // table; an empty existing table means the admin removed all cards.
        try {
            $certificationsTableExists = (bool) $pdo->query("SHOW TABLES LIKE 'certifications'")->fetchColumn();
            $pdo->exec("CREATE TABLE IF NOT EXISTS certifications (
                id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL, title VARCHAR(180) NOT NULL,
                description TEXT NOT NULL, image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active',
                display_order INT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_certifications_public (status, display_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!$certificationsTableExists && (int)$pdo->query('SELECT COUNT(*) FROM certifications')->fetchColumn() === 0) {
                $insertCert = $pdo->prepare("INSERT INTO certifications (category, title, description, image_url, status, display_order) VALUES (?, ?, ?, ?, 'active', ?)");
                $insertCert->execute(['PILOT QUALIFICATION', 'IDTC Remote Pilot Qualification', 'Penilaian standar terbang mencakup kelaikan wahana, SOP darurat, dan manajemen risiko ruang udara.', 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1200&auto=format&fit=crop', 1]);
            }
            $stmtCert = $pdo->query("SELECT * FROM certifications WHERE status = 'active' ORDER BY display_order ASC, id ASC");
            $dynamicCertifications = $stmtCert->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $dynamicCertifications = []; }
        
        // Also fetch CMS Content if table exists
        try {
            $stmtCms = $pdo->query("SELECT content_key, content_value FROM site_content");
            foreach ($stmtCms->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $dynamicCms[$row['content_key']] = $row['content_value'];
            }
        } catch (Exception $e) {}
    } catch (Exception $e) {
        // Fallback to empty if tables don't exist
    }
}

$heroHeadline = trim((string) ($dynamicCms['hero-headline'] ?? "INDONESIA\nDRONE\nTRAINING\nCENTER"));
$heroHeadlineLines = array_slice(preg_split('/\s+/', $heroHeadline, -1, PREG_SPLIT_NO_EMPTY), 0, 4);

// "Find Us" contact settings — editable in Admin > Site Content (CMS) > Find Us.
// Keys: contact-email, contact-whatsapp, contact-address, contact-instagram.
$contactDefaults = [
    'contact-email' => 'jogjacreativeproduction@gmail.com',
    'contact-whatsapp' => '+62 856-0060-4388',
    'contact-address' => 'Perum Jl. Griya Mlati Indah No. B4, Mulungan Kulon, Sendangadi, Kec. Mlati, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55285',
    'contact-instagram' => '@idtc.drone'
];
$contact = array_merge($contactDefaults, array_map('trim', array_filter($dynamicCms, static fn($value) => $value !== null && trim((string)$value) !== '')));
$contactEmail = $contact['contact-email'];
$contactWhatsapp = $contact['contact-whatsapp'];
$contactWhatsappDigits = preg_replace('/\D+/', '', $contactWhatsapp) ?: '6285600604388';
$contactAddress = $contact['contact-address'];
$contactInstagram = $contact['contact-instagram'];
$instagramHandle = ltrim($contactInstagram, '@');
$contactWhatsappLink = 'https://wa.me/' . $contactWhatsappDigits;
$contactMailtoLink = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode($contactEmail);
// maps.google.com/maps?q=...&iwloc=&output=embed reliably drops a red pin at the
// geocoded address (no API key needed), so the marker follows any address change.
$contactMapEmbed = 'https://maps.google.com/maps?q=' . rawurlencode($contactAddress) . '&t=m&z=16&ie=UTF8&iwloc=&output=embed';
$contactMapDirections = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($contactAddress);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IDTC | Master The Sky - Indonesia Drone Training Center</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="IDTC — Indonesia Drone Training Center by PT Jogja Creative Production. Pelatihan drone profesional, keselamatan penerbangan, fotogrametri mapping, dan dokumentasi udara di Yogyakarta.">
    <meta name="keywords" content="IDTC, Indonesia Drone Training Center, pelatihan drone jogja, training drone profesional, pemetaan drone, sertifikasi pilot drone">
    <meta name="author" content="IDTC - PT Jogja Creative Production">
    
    <!-- Open Graph Metadata -->
    <meta property="og:title" content="IDTC | Master The Sky - Indonesia Drone Training Center">
    <meta property="og:description" content="Pelatihan drone profesional berfokus pada keterampilan terbang, pengoperasian kamera, dan keselamatan penerbangan di Yogyakarta.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:image" content="https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1600&auto=format&fit=crop">

    <!-- Tailwind CSS (via CDN) & Corporate Design System -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'primary-orange': '#FF5E00',
              'primary-obsidian': '#080808',
              'dark-surface': '#111111',
              'dark-elevated': '#181818',
              'text-muted': '#888888',
              'border-subtle': '#222222'
            },
            fontFamily: {
              heading: ['Plus Jakarta Sans', 'sans-serif'],
              body: ['Inter', 'sans-serif']
            }
          }
        }
      }
    </script>
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
    <link rel="stylesheet" href="css/animations.css">
</head>
<body class="bg-primary-obsidian text-slate-100 font-body antialiased selection:bg-primary-orange selection:text-white">

    <!-- ============================================================================== -->
    <!-- 1. NAVBAR — PREMIUM TRANSPARENT → SOLID -->
    <!-- ============================================================================== -->
    <header class="navbar-sticky">
        <div class="navbar-shell container-custom flex items-center justify-between">
            <!-- Brand -->
            <a href="#hero" class="navbar-brand flex items-center gap-3.5 group">
                <span class="navbar-logo-frame"><img src="logo/Logo%20IDTC.png" alt="Logo IDTC" class="h-10 w-10 shrink-0 object-contain" /></span>
                <div class="flex flex-col justify-center">
                    <span class="font-heading font-black text-xl sm:text-2xl tracking-tight text-white block leading-none">IDTC</span>
                    <span class="text-[10px] sm:text-xs font-bold text-slate-300 block tracking-widest uppercase mt-1 leading-tight">INDONESIA DRONE TRAINING CENTER</span>
                </div>
            </a>

            <!-- Desktop Links -->
            <nav class="navbar-desktop-links hidden xl:flex items-center gap-8 text-xs md:text-sm font-bold uppercase tracking-wider text-white/70">
                <a href="#expertise" class="hover:text-primary-orange transition-colors" data-i18n="nav-expertise">Project & Training</a>
                <a href="#certification" class="hover:text-primary-orange transition-colors" data-i18n="nav-certification">Certification</a>
                <a href="#gallery" class="hover:text-primary-orange transition-colors" data-i18n="nav-gallery">Gallery</a>
                <a href="#news" class="hover:text-primary-orange transition-colors" data-i18n="nav-news">Artikel</a>
                <a href="#credentials" class="hover:text-primary-orange transition-colors" data-i18n="nav-credentials">Credentials</a>
                <a href="#faq" class="hover:text-primary-orange transition-colors" data-i18n="nav-faq">FAQ</a>
            </nav>

            <!-- Action Button & Language Switcher -->
            <div class="navbar-actions hidden xl:flex items-center gap-4">
                    <a href="#tab-content-training" onclick="openTrainingSection(event)" class="navbar-cta btn-primary text-xs py-3 px-6 font-bold">
                    <span data-i18n="btn-register">Explore Training</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
                <!-- Language Switcher & Waktu Indonesia — satu toolbar pill (ID|EN lalu jam di kanannya) -->
                <div class="navbar-tools flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/[0.06] px-1.5 py-1">
                    <div class="lang-switcher inline-flex items-center rounded-lg p-0.5 text-xs font-bold">
                        <button type="button" onclick="switchLanguage('id')" id="lang-btn-id" class="px-2.5 py-1 rounded-md text-white bg-primary-orange transition-all">ID</button>
                        <button type="button" onclick="switchLanguage('en')" id="lang-btn-en" class="px-2.5 py-1 rounded-md text-white/40 hover:text-white transition-all">EN</button>
                    </div>
                    <span class="block h-5 w-px shrink-0 bg-white/15" aria-hidden="true"></span>
                    <div id="idtc-indonesia-clocks" class="idtc-clock-nav" title="Jam Indonesia — otomatis menyesuaikan WIB / WITA / WIT dengan lokasi Anda"></div>
                </div>
            </div>

            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-toggle" class="xl:hidden text-white/70 hover:text-white p-2" aria-label="Open Navigation">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </header>

    <!-- Mobile Navigation Drawer -->
    <div id="mobile-drawer" class="fixed inset-0 bg-primary-obsidian/95 backdrop-blur-2xl z-50 flex flex-col justify-between p-6 hidden xl:hidden">
        <div>
            <div class="flex items-center justify-between pb-6 border-b border-border-subtle">
                <div class="flex items-center gap-3.5">
                    <span class="navbar-logo-frame navbar-logo-frame-mobile"><img src="logo/Logo%20IDTC.png" alt="Logo IDTC" class="h-8 w-8 shrink-0 object-contain" /></span>
                    <div class="flex flex-col justify-center">
                        <span class="font-heading font-black text-lg text-white block leading-none">IDTC</span>
                        <span class="text-[10px] font-bold text-slate-300 block tracking-wider uppercase mt-1 leading-tight">PT Jogja Creative Production</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Mobile Language Switcher -->
                    <div class="lang-switcher inline-flex items-center bg-black/60 border border-white/10 rounded-lg p-1 text-xs font-bold">
                        <button type="button" onclick="switchLanguage('id')" id="mob-lang-btn-id" class="px-2.5 py-1 rounded text-white bg-primary-orange transition-all">ID</button>
                        <button type="button" onclick="switchLanguage('en')" id="mob-lang-btn-en" class="px-2.5 py-1 rounded text-slate-400 hover:text-white transition-all">EN</button>
                    </div>
                    <!-- Waktu Indonesia (mini) -->
                    <div data-idtc-clock class="idtc-clock-mini" title="Jam Indonesia — otomatis menyesuaikan WIB / WITA / WIT dengan lokasi Anda"></div>
                    <button id="close-drawer-btn" class="text-slate-400 hover:text-white p-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
            <nav class="flex flex-col gap-6 text-lg font-extrabold uppercase tracking-wider text-slate-200 mt-8">
                <a href="#expertise" class="hover:text-primary-orange" data-i18n="nav-expertise">Project & Training</a>
                <a href="#certification" class="hover:text-primary-orange" data-i18n="nav-certification">Certification</a>
                <a href="#gallery" class="hover:text-primary-orange" data-i18n="nav-gallery">Gallery</a>
                <a href="#news" class="hover:text-primary-orange" data-i18n="nav-news">Artikel</a>
                <a href="#credentials" class="hover:text-primary-orange" data-i18n="nav-credentials">Credentials</a>
                <a href="#faq" class="hover:text-primary-orange" data-i18n="nav-faq">FAQ</a>
            </nav>
        </div>
        <div class="pt-6 border-t border-border-subtle">
            <a href="#tab-content-training" onclick="openTrainingSection(event)" class="btn-primary w-full py-4 text-center block text-base font-bold">
                <span data-i18n="btn-explore-training">Explore Training</span>
            </a>
        </div>
    </div>


    <!-- ============================================================================== -->
    <!-- 2. HERO SECTION — FULL-BLEED DRONE BACKGROUND -->
    <!-- ============================================================================== -->
    <section id="hero" class="hero-viewport-100">
        <!-- Full-bleed drone background -->
        <div class="hero-bg-parallax" data-hero-background></div>

        <!-- Cinematic directional gradient overlay -->
        <div class="hero-cinematic-overlay"></div>

        <div class="container-custom relative z-10 pt-36 pb-24">
            <div class="max-w-4xl">

                <!-- Headline -->
                <h1 class="hero-headline-text mb-6" data-hero-headline>
                    <?php foreach ($heroHeadlineLines as $heroHeadlineLine): ?><span class="hero-headline-line"><?php echo htmlspecialchars($heroHeadlineLine, ENT_QUOTES, 'UTF-8'); ?></span><?php endforeach; ?>
                </h1>

                <!-- Subtitle -->
                <p class="text-2xl sm:text-3xl md:text-4xl font-bold text-white/95 mb-5 tracking-tight leading-snug" data-i18n="hero-subtitle">
                    Professional Drone Training for the Future.
                </p>

                <!-- Description -->
                <p class="text-base sm:text-lg md:text-xl text-slate-200 max-w-2xl mb-10 leading-relaxed" data-i18n="hero-desc">
                    Layanan pelatihan drone profesional berfokus pada keterampilan terbang, pengoperasian kamera, dan keselamatan penerbangan langsung di lapangan.
                </p>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                    <a href="#tab-content-training" onclick="openTrainingSection(event)" data-hero-link="primary" class="btn-primary py-4 px-9 text-base font-bold text-center">
                        <span data-i18n="hero-cta-primary">Explore Training</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                    <a href="<?php echo htmlspecialchars($contactWhatsappLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" data-hero-link="secondary" class="btn-secondary py-4 px-9 text-base font-bold text-center">
                        <span data-i18n="hero-cta-secondary">Contact IDTC</span>
                    </a>
                </div>

            </div>
        </div>
    </section>


    <!-- ============================================================================== -->
    <!-- 3. ABOUT SECTION ("ENGINEERED FOR THE SKY.") -->
    <!-- ============================================================================== -->

    <!-- ============================================================================== -->
    <!-- ============================================================================== -->
    <!-- 4. OUR EXPERTISE (PROJECTS, TRAINING, SERVICES) -->
    <!-- ============================================================================== -->
    <section id="expertise" class="py-28 md:py-36 relative bg-dark-surface border-t border-border-subtle">
        <div class="container-custom fade-in-up">

            <!-- Master Unified Section Header -->
            <div class="mb-12">
                <div class="badge-eyebrow" data-i18n="expertise-eyebrow">LAYANAN KAMI</div>
                <h2 class="text-[1.6rem] sm:text-[2.9rem] md:text-[3.5rem] font-black text-white uppercase tracking-tight leading-none mb-6" data-i18n="expertise-title">PROJECT & TRAINING</h2>
                <p class="text-slate-300 max-w-2xl text-base sm:text-lg font-medium leading-relaxed" data-i18n="expertise-desc">
                    Portofolio proyek dan program pelatihan drone profesional dari IDTC.
                </p>
            </div>

            <!-- Tab Selector -->
            <div class="flex flex-wrap items-center gap-3 mb-16 pb-8 border-b border-white/10" id="expertise-tabs">
                <button type="button" data-tab="projects" class="expertise-tab-btn active px-6 py-3.5 rounded-xl bg-primary-orange text-white text-xs sm:text-sm font-extrabold uppercase tracking-wider hover:bg-orange-600 transition-all flex items-center gap-2.5 shadow-lg shadow-primary-orange/20">
                    <span class="w-2 h-2 rounded-full bg-white animate-ping marker-dot"></span>
                    <span data-i18n="tab-projects">01. PROJECTS</span>
                </button>
                <button type="button" data-tab="training" class="expertise-tab-btn px-6 py-3.5 rounded-xl bg-white/5 border border-white/15 text-slate-200 hover:text-white hover:bg-white/10 text-xs sm:text-sm font-extrabold uppercase tracking-wider transition-all flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary-orange hidden marker-dot"></span>
                    <span data-i18n="tab-training">02. TRAINING</span>
                </button>
            </div>

            <!-- Active Content Container -->
            <div id="expertise-content-wrapper" class="relative">

                <!-- SUB-BLOCK 01: SELECTED PROJECTS -->
                <div id="tab-content-projects" class="expertise-content-pane transition-opacity duration-500">
                    <div class="mb-10">
                        <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
                            <div>
                                <div class="badge-eyebrow" data-i18n="projects-eyebrow">PORTOPOLIO REKAM JEJAK</div>
                                <h3 class="text-[1.4rem] sm:text-[2.6rem] md:text-[3.1rem] font-black text-white uppercase tracking-tight" data-i18n="projects-title">OUR PROJECTS</h3>
                            </div>
                        </div>

                        <!-- Horizontal Expanding Cards Container -->
                        <div id="projects-cards-container" class="projects-showcase-wrapper mb-10">
                            <!-- Dynamically rendered by JS projectsData state -->
                        </div>

                        <!-- Important Information / Metrics Panel -->
                        <div id="info-panel-box" class="info-panel-box">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                                <div class="lg:col-span-6 space-y-3">
                                    <span id="info-panel-category" class="text-xs sm:text-sm font-extrabold uppercase tracking-widest text-primary-orange block">CATEGORY</span>
                                    <h4 id="info-panel-title" class="text-3xl sm:text-5xl font-black text-white uppercase">Project Title</h4>
                                    <p id="info-panel-subtitle" class="text-base sm:text-lg font-bold text-slate-200">Project Subtitle</p>
                                    <p id="info-panel-desc" class="text-sm sm:text-base text-slate-300 leading-relaxed">
                                        Project case study details load dynamically from state.
                                    </p>
                                </div>

                                <div class="lg:col-span-6 space-y-6">
                                    <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-primary-orange block" data-i18n="projects-metrics-title">IMPORTANT INFORMATION</span>
                                    <div id="info-panel-stats-grid" class="grid grid-cols-2 gap-4">
                                        <!-- Metrics render dynamically -->
                                    </div>
                                    <div>
                                        <button id="info-panel-cta" onclick="openCaseStudyModal(0)" class="btn-primary w-full sm:w-auto text-sm py-3.5 px-8 font-bold">
                                            <span data-i18n="projects-btn-modal">View Project</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-BLOCK 02: TRAINING PROGRAMS -->
                <div id="tab-content-training" class="expertise-content-pane hidden opacity-0 transition-opacity duration-500">
                    <div class="mb-10">
                        <div class="badge-eyebrow" data-i18n="training-eyebrow">PROGRAM & MODUL PELATIHAN</div>
                        <h3 class="text-[1.4rem] sm:text-[2.6rem] font-black text-white uppercase" data-i18n="training-title">TRAINING PROGRAMS</h3>
                        <p class="text-slate-300 text-base sm:text-lg mt-2 max-w-2xl leading-relaxed" data-i18n="training-desc">
                            Materi dan durasi pelatihan menyesuaikan kemampuan awal peserta serta kebutuhan organisasi (hobi, dokumentasi profesional, hingga industri).
                        </p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        <!-- Left Tabs Selector -->
                        <div id="training-tabs-container" class="lg:col-span-5 flex flex-col border border-border-subtle rounded-lg overflow-hidden">
                            <!-- Rendered by JS -->
                        </div>

                        <!-- Right Detail Display Card -->
                        <div class="lg:col-span-7">
                            <div id="training-detail-card" class="corporate-card bg-dark-elevated h-full flex flex-col justify-between">
                                <!-- Rendered by JS -->
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- End Expertise Content Wrapper -->

        </div>
    </section>


    <!-- ============================================================================== -->
    <!-- 7. WHY IDTC (PREMIUM GLASS FEATURE CARDS) -->
    <!-- ============================================================================== -->
    <section class="why-idtc-section py-28 md:py-36 bg-dark-surface border-y border-border-subtle relative overflow-hidden">
        <!-- Ambient decorative glows -->
        <div class="why-ambient why-ambient-a" aria-hidden="true"></div>
        <div class="why-ambient why-ambient-b" aria-hidden="true"></div>

        <div class="container-custom fade-in-up relative">
            <!-- Section header -->
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-8 mb-16 md:mb-20">
                <div class="max-w-2xl">
                    <div class="badge-eyebrow" data-i18n="why-eyebrow">KEUNGGULAN IDTC</div>
                    <h2 class="text-[1.6rem] sm:text-[2.9rem] md:text-[3.5rem] font-black text-white uppercase tracking-tight leading-none mb-6" data-i18n="why-title">WHY IDTC?</h2>
                    <p class="text-slate-300 max-w-2xl text-base sm:text-lg font-medium leading-relaxed" data-i18n="why-desc">
                        Tiga standar utama yang menjadikan setiap program pelatihan IDTC aman, presisi, dan berkelas profesional.
                    </p>
                </div>
                <div class="why-stamp shrink-0" aria-hidden="true">
                    <span>TRUSTED</span>
                    <b>STANDARD</b>
                    <span>FLIGHT TRAINING</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                <!-- Card 1 -->
                <article class="why-card group">
                    <span class="why-card-index" aria-hidden="true">01</span>
                    <div class="why-card-glow" aria-hidden="true"></div>
                    <div class="why-icon-tile group-hover:scale-105">
                        <!-- Pilot Wings / Instructor Icon -->
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                    <span class="why-card-tag" data-i18n="why-card1-num">[01 / INSTRUCTOR]</span>
                    <h3 class="why-card-title" data-i18n="why-card1-title">Instruktur Berpengalaman</h3>
                    <p class="why-card-desc" data-i18n="why-card1-desc">Peserta belajar langsung melalui praktik di lapangan dengan panduan instruktur praktisi berpengalaman.</p>
                    <div class="why-card-footer">
                        <span class="why-card-bar"></span>
                        <span class="why-card-hint">EXPERT MENTORSHIP</span>
                    </div>
                </article>

                <!-- Card 2 -->
                <article class="why-card group">
                    <span class="why-card-index" aria-hidden="true">02</span>
                    <div class="why-card-glow" aria-hidden="true"></div>
                    <div class="why-icon-tile group-hover:scale-105">
                        <!-- Shield Safety Icon -->
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <span class="why-card-tag" data-i18n="why-card2-num">[02 / SAFETY FIRST]</span>
                    <h3 class="why-card-title" data-i18n="why-card2-title">Safety & SOP Standar</h3>
                    <p class="why-card-desc" data-i18n="why-card2-desc">Fokus pada teknik penerbangan yang aman, pemahaman prosedur operasional sesuai standar keselamatan penerbangan.</p>
                    <div class="why-card-footer">
                        <span class="why-card-bar"></span>
                        <span class="why-card-hint">SAFETY-CRITICAL SOP</span>
                    </div>
                </article>

                <!-- Card 3 -->
                <article class="why-card group">
                    <span class="why-card-index" aria-hidden="true">03</span>
                    <div class="why-card-glow" aria-hidden="true"></div>
                    <div class="why-icon-tile group-hover:scale-105">
                        <!-- Precision Camera Icon -->
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="why-card-tag" data-i18n="why-card3-num">[03 / PRECISION]</span>
                    <h3 class="why-card-title" data-i18n="why-card3-title">Footage Stabil & Presisi</h3>
                    <p class="why-card-desc" data-i18n="why-card3-desc">Keterampilan pengoperasian kamera canggih untuk menghasilkan visual sinematik yang stabil dan data spasial presisi.</p>
                    <div class="why-card-footer">
                        <span class="why-card-bar"></span>
                        <span class="why-card-hint">CINEMATIC & SPATIAL</span>
                    </div>
                </article>
            </div>
        </div>
    </section>


    <!-- ============================================================================== -->
    <!-- 8. TRAINING PROCESS -->
    <!-- ============================================================================== -->
    <!-- 9. CERTIFICATION -->
    <!-- ============================================================================== -->
    <section id="certification" class="py-28 md:py-36 bg-dark-surface border-t border-border-subtle relative overflow-hidden">
        <div class="container-custom fade-in-up">
            <div class="max-w-3xl mb-16">
                <div class="badge-eyebrow" data-i18n="cert-eyebrow">STANDARISASI &amp; KUALIFIKASI</div>
                <h2 class="certification-heading font-black text-white uppercase tracking-tight leading-none" data-i18n="cert-title">CERTIFICATION &amp; SAFETY</h2>
                <p class="text-slate-300 max-w-2xl text-base sm:text-lg mt-4 leading-relaxed" data-i18n="cert-desc">
                    Sertifikasi IDTC dirancang untuk memastikan setiap peserta menguasai teknik penerbangan yang aman dan mematuhi standar keselamatan operasional di industri.
                </p>
            </div>

            <?php if (!empty($dynamicCertifications)): ?>
                <div class="cert-card-grid">
                    <?php foreach ($dynamicCertifications as $cert): ?>
                    <article class="cert-card">
                        <div class="cert-card-media">
                            <?php if (!empty($cert['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($cert['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($cert['title'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                            <?php endif; ?>
                            <span class="cert-card-badge"><?php echo htmlspecialchars($cert['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="cert-card-body">
                            <h3 class="cert-card-title"><?php echo htmlspecialchars($cert['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="cert-card-desc"><?php echo htmlspecialchars($cert['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>


    <!-- ============================================================================== -->
    <!-- 10. GALLERY — FOLDER-BASED DOCUMENTATION ARCHIVE -->
    <!-- ============================================================================== -->
    <section id="gallery" class="py-28 md:py-36 relative">
        <div class="container-custom fade-in-up">
            <div class="max-w-3xl mb-16">
                <div class="badge-eyebrow" data-i18n="gallery-eyebrow">DOKUMENTASI LAPANGAN</div>
                <h2 class="text-[1.4rem] sm:text-[2.6rem] font-black text-white uppercase" data-i18n="gallery-title">GALLERY</h2>
                <p class="text-slate-300 text-base sm:text-lg mt-3 max-w-2xl leading-relaxed" data-i18n="gallery-desc">
                    Dokumentasi kegiatan, pelatihan, proyek, dan aktivitas PT Drone Trainee Center.
                </p>
            </div>

            <!-- Folder Grid -->
            <div id="public-gallery-folders" class="gallery-single-folder-wrap">
                <a href="gallery.php?category=training" class="gallery-folder group" id="folder-training">
                    <div class="folder-tab"></div>
                    <div class="folder-body">
                        <div class="folder-preview">
                            <img src="https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=400&auto=format&fit=crop" alt="Training" loading="lazy">
                            <div class="folder-preview-overlay"></div>
                            <div class="folder-open-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            </div>
                        </div>
                        <div class="folder-info">
                            <h4 class="folder-name">TRAINING</h4>
                            <p class="folder-count">24 Photos</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- ============================================================================== -->
    <!-- 10B. NEWS & ARTICLES SECTION -->
    <!-- ============================================================================== -->
    <section id="news" class="py-28 md:py-36 bg-primary-obsidian border-t border-border-subtle relative">
        <div class="container-custom fade-in-up">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
                <div>
                    <div class="badge-eyebrow" data-i18n="news-eyebrow">INFORMASI & JURNAL KRITIS</div>
                    <h2 class="text-[1.4rem] sm:text-[2.6rem] font-black text-white uppercase tracking-tight" data-i18n="news-title">BERITA & ARTIKEL TERBARU</h2>
                    <p class="text-slate-300 text-base sm:text-lg mt-3 max-w-2xl leading-relaxed" data-i18n="news-desc">
                        Informasi terkini mengenai industri drone Indonesia, regulasi penerbangan, pelatihan sertifikasi, dan teknologi penerbangan masa depan.
                    </p>
                </div>
            </div>

            <!-- Public Articles Grid Container -->
            <div id="public-news-grid" class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <!-- Dynamic Content Rendered Here via JS -->
            </div>
            <div id="public-news-more" class="mt-10 hidden text-center">
                <a href="artikel.php" class="btn-primary inline-flex items-center gap-2 px-7 py-3 text-sm font-bold">
                    <span>Lihat Semua Artikel</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>
        </div>
    </section>


    <!-- ============================================================================== -->
    <!-- 11. TESTIMONIALS -->
    <!-- ============================================================================== -->
    <section class="py-28 md:py-36 bg-dark-surface border-y border-border-subtle relative">
        <div class="container-custom fade-in-up">
            <div class="max-w-3xl mb-16">
                <div class="badge-eyebrow" data-i18n="testimonials-eyebrow">TESTIMONI ALUMNI</div>
                <h2 class="text-[1.3rem] sm:text-[2.4rem] font-black text-white uppercase" data-i18n="testimonials-title">INDUSTRY FEEDBACK</h2>
            </div>

            <div id="public-testimonials-grid" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php if (empty($dynamicTestimonials)): ?>
                    <div class="col-span-full text-center text-slate-500 py-10">Belum ada testimoni.</div>
                <?php else: ?>
                    <?php foreach ($dynamicTestimonials as $t): ?>
                        <div class="corporate-card flex flex-col justify-between">
                            <p class="text-slate-200 text-base sm:text-lg leading-relaxed italic mb-6">
                                "<?php echo htmlspecialchars($t['testimonial'], ENT_QUOTES, 'UTF-8'); ?>"
                            </p>
                            <div class="pt-4 border-t border-border-subtle flex items-center gap-4">
                                <?php if (!empty($t['photo_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($t['photo_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="Photo" class="w-12 h-12 rounded-full object-cover">
                                <?php endif; ?>
                                <div>
                                    <h4 class="text-white font-bold text-base"><?php echo htmlspecialchars($t['customer_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p class="text-xs sm:text-sm text-slate-400 font-medium mt-0.5"><?php echo htmlspecialchars($t['role'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>


    <!-- ============================================================================== -->
    <!-- 12. TRUSTED & RECOGNIZED (Credibility / Institutional Section) -->
    <!-- ============================================================================== -->
    <section id="credentials" class="credentials-section relative">
        <div class="container-custom fade-in-up">

            <!-- Section Header -->
            <div class="max-w-2xl mb-20">
                <div class="badge-eyebrow" style="color: var(--primary-orange);" data-i18n="credentials-eyebrow">NETWORK & CREDENTIALS</div>
                <h2 class="text-5xl sm:text-7xl font-black uppercase tracking-tight leading-none" style="color: #111;" data-i18n="credentials-title">
                    TRUSTED & RECOGNIZED
                </h2>
                <p class="text-base sm:text-lg mt-4 leading-relaxed font-medium" style="color: #555;" data-i18n="credentials-desc">
                    Building professional drone capabilities through industry standards, qualified training, regulatory compliance, and trusted partnerships.
                </p>
            </div>

            <?php if (!$pdo || empty($dynamicCredentials)): ?>
            <!-- ========== CATEGORY 01: REGULATORY & AVIATION ========== -->
            <div class="credentials-category">
                <div class="credentials-category-header">
                    <span class="credentials-cat-number">01</span>
                    <h3 class="credentials-cat-title" data-i18n="cred-cat1-title">REGULATORY & AVIATION</h3>
                </div>
                <div class="credentials-logo-grid">
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200">
                            <img src="https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?q=80&w=300&auto=format&fit=crop" alt="Aviation Regulation" class="w-full h-full object-cover">
                        </div>
                        <span class="credential-org-name text-base font-bold" data-i18n="cred-org1-name">DKPPU / Kemenhub RI</span>
                        <span class="credential-relationship text-xs font-semibold" data-i18n="cred-org1-rel">Regulasi & Keselamatan Penerbangan</span>
                    </div>
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200">
                            <img src="https://images.unsplash.com/photo-1519074069444-1ba4edd16be1?q=80&w=300&auto=format&fit=crop" alt="Airspace Compliance" class="w-full h-full object-cover">
                        </div>
                        <span class="credential-org-name text-base font-bold" data-i18n="cred-org2-name">AirNav Indonesia</span>
                        <span class="credential-relationship text-xs font-semibold" data-i18n="cred-org2-rel">Izin Wilayah Udara & NOTAM</span>
                    </div>
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200">
                            <img src="https://images.unsplash.com/photo-1577495508048-b635879837f1?q=80&w=300&auto=format&fit=crop" alt="Disnaker Sleman" class="w-full h-full object-cover">
                        </div>
                        <span class="credential-org-name text-base font-bold" data-i18n="cred-org3-name">Disnaker Kab. Sleman</span>
                        <span class="credential-relationship text-xs font-semibold" data-i18n="cred-org3-rel">Instansi Mitra In-House Training</span>
                    </div>
                </div>
            </div>

            <!-- ========== CATEGORY 02: CERTIFICATION & STANDARDS ========== -->
            <div class="credentials-category">
                <div class="credentials-category-header">
                    <span class="credentials-cat-number">02</span>
                    <h3 class="credentials-cat-title" data-i18n="cred-cat2-title">CERTIFICATION & PROFESSIONAL STANDARDS</h3>
                </div>
                <div class="credentials-logo-grid">
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200">
                            <img src="https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=300&auto=format&fit=crop" alt="Standard Kompetensi" class="w-full h-full object-cover">
                        </div>
                        <span class="credential-org-name text-base font-bold" data-i18n="cred-org4-name">Sertifikasi BNSP / Pilot</span>
                        <span class="credential-relationship text-xs font-semibold" data-i18n="cred-org4-rel">Standar Kompetensi Pilot Drone</span>
                    </div>
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200">
                            <img src="https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=300&auto=format&fit=crop" alt="FASI Aero Sport" class="w-full h-full object-cover">
                        </div>
                        <span class="credential-org-name text-base font-bold" data-i18n="cred-org5-name">FASI (Aero Sport Indonesia)</span>
                        <span class="credential-relationship text-xs font-semibold" data-i18n="cred-org5-rel">Standar Navigasi & Olahraga Udara</span>
                    </div>
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200">
                            <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=300&auto=format&fit=crop" alt="ISO Safety SOP" class="w-full h-full object-cover">
                        </div>
                        <span class="credential-org-name text-base font-bold" data-i18n="cred-org6-name">ISO Safety Flight SOP</span>
                        <span class="credential-relationship text-xs font-semibold" data-i18n="cred-org6-rel">Standard Operasional & Prosedur Risk Management</span>
                    </div>
                </div>
            </div>

            <!-- ========== CATEGORY 03: INDUSTRY & TECHNOLOGY ========== -->
            <div class="credentials-category">
                <div class="credentials-category-header">
                    <span class="credentials-cat-number">03</span>
                    <h3 class="credentials-cat-title" data-i18n="cred-cat3-title">INDUSTRY & TECHNOLOGY PARTNERS</h3>
                </div>
                <div class="credentials-logo-grid">
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200">
                            <img src="https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=300&auto=format&fit=crop" alt="DJI Enterprise" class="w-full h-full object-cover">
                        </div>
                        <span class="credential-org-name text-base font-bold" data-i18n="cred-org7-name">DJI Enterprise Platform</span>
                        <span class="credential-relationship text-xs font-semibold" data-i18n="cred-org7-rel">Armada Wahana Udara & Sensor</span>
                    </div>
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200">
                            <img src="https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=300&auto=format&fit=crop" alt="Pix4D Agisoft Mapping" class="w-full h-full object-cover">
                        </div>
                        <span class="credential-org-name text-base font-bold" data-i18n="cred-org8-name">Pix4D & Agisoft Photogrammetry</span>
                        <span class="credential-relationship text-xs font-semibold" data-i18n="cred-org8-rel">Software Pengolahan Data Spasial</span>
                    </div>
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200">
                            <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=300&auto=format&fit=crop" alt="PT Jogja Creative Production" class="w-full h-full object-cover">
                        </div>
                        <span class="credential-org-name text-base font-bold" data-i18n="cred-org9-name">PT Jogja Creative Production</span>
                        <span class="credential-relationship text-xs font-semibold" data-i18n="cred-org9-rel">Perusahaan Induk (Parent Enterprise)</span>
                    </div>
                </div>
            </div>

            <?php else: $credGroupNumber = 0; $credGroups = [];
                foreach ($dynamicCredentials as $credItem) {
                    $credCategory = trim((string)($credItem['category'] ?? '')) !== '' ? trim((string)$credItem['category']) : 'NETWORK & CREDENTIALS';
                    $credGroups[$credCategory][] = $credItem;
                }
            ?>
            <?php foreach ($credGroups as $credCategory => $credItems): $credGroupNumber++; ?>
            <div class="credentials-category">
                <div class="credentials-category-header">
                    <span class="credentials-cat-number"><?php echo str_pad((string)$credGroupNumber, 2, '0', STR_PAD_LEFT); ?></span>
                    <h3 class="credentials-cat-title"><?php echo htmlspecialchars($credCategory, ENT_QUOTES, 'UTF-8'); ?></h3>
                </div>
                <div class="credentials-logo-grid">
                    <?php foreach ($credItems as $credItem): ?>
                    <div class="credential-logo-item">
                        <div class="credential-logo-placeholder overflow-hidden border border-slate-200<?php echo empty($credItem['image_url']) ? ' flex items-center justify-center bg-slate-50' : ''; ?>">
                            <?php if (!empty($credItem['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($credItem['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($credItem['org_name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" class="w-full h-full object-cover">
                            <?php else: ?>
                            <span class="text-2xl font-black text-slate-300 select-none"><?php echo htmlspecialchars(((function_exists('mb_substr') ? mb_substr($credItem['org_name'], 0, 1) : substr($credItem['org_name'], 0, 1)) ?: '•'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="credential-org-name text-base font-bold"><?php echo htmlspecialchars($credItem['org_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="credential-relationship text-xs font-semibold"><?php echo htmlspecialchars($credItem['relationship'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>


    <!-- ============================================================================== -->
    <!-- 13. CONTACT US -->
    <!-- ============================================================================== -->
    <section id="faq" class="py-28 md:py-36 relative bg-dark-surface border-y border-border-subtle">
        <div class="container-custom fade-in-up">
            <div class="space-y-10">
                <div class="space-y-6">
                    <div class="text-xs sm:text-sm font-black uppercase tracking-[0.22em] text-primary-orange">(FIND US)</div>

                    <div class="space-y-6 border-t border-white/10 pt-5">
                        <div>
                            <div class="text-[10px] sm:text-[11px] font-black uppercase tracking-[0.2em] text-primary-orange">EMAIL</div>
                            <a href="<?php echo htmlspecialchars($contactMailtoLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="mt-3 block border-b border-white/15 pb-3 text-lg font-medium text-white transition hover:border-primary-orange hover:text-primary-orange sm:text-xl"><?php echo htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>

                        <div>
                            <div class="text-[10px] sm:text-[11px] font-black uppercase tracking-[0.2em] text-primary-orange">WHATSAPP</div>
                            <a href="<?php echo htmlspecialchars($contactWhatsappLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="mt-3 block border-b border-white/15 pb-3 text-lg font-medium text-white transition hover:border-primary-orange hover:text-primary-orange sm:text-xl"><?php echo htmlspecialchars($contactWhatsapp, ENT_QUOTES, 'UTF-8'); ?> <span class="inline-block transform rotate-[-12deg] text-primary-orange">↗</span></a>
                        </div>

                        <div>
                            <div class="text-[10px] sm:text-[11px] font-black uppercase tracking-[0.2em] text-primary-orange">STUDIO</div>
                            <div class="mt-3 border-b border-white/15 pb-3 text-lg sm:text-xl font-medium text-white leading-relaxed"><?php echo htmlspecialchars($contactAddress, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-xl border border-white/10 bg-[#151515] shadow-[0_20px_50px_rgba(0,0,0,0.35)]">
                        <iframe
                            title="Lokasi kantor PT Jogja Creative Production"
                            src="<?php echo htmlspecialchars($contactMapEmbed, ENT_QUOTES, 'UTF-8'); ?>"
                            class="h-72 w-full border-0 sm:h-96"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                        <div class="flex flex-col gap-3 border-t border-white/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm font-semibold text-slate-200">PT Jogja Creative Production</p>
                            <a href="<?php echo htmlspecialchars($contactMapDirections, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-wider text-primary-orange hover:text-orange-300">
                                Buka Petunjuk Arah
                                <span aria-hidden="true">↗</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ============================================================================== -->
    <!-- 14. CTA BANNER -->
    <!-- ============================================================================== -->
    <section class="py-28 md:py-36 border-t border-border-subtle bg-dark-surface relative overflow-hidden">
        <div class="container-custom fade-in-up text-center max-w-3xl mx-auto">
            <h2 class="text-4xl sm:text-6xl font-black text-white uppercase tracking-tight mb-6 leading-tight">
                MAKE THE NEXT MOVE MATTER.
            </h2>
            <p class="text-slate-300 text-base mb-10">
                Diskusikan kebutuhan pelatihan drone profesional bersama tim Indonesia Drone Training Center (IDTC).
            </p>
            <a href="<?php echo htmlspecialchars($contactWhatsappLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn-primary py-4 px-10 inline-flex items-center gap-2">
                <span>Diskusikan Kebutuhan Anda (WhatsApp)</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </section>


    <!-- ============================================================================== -->
    <!-- 14. FOOTER (UPDATED WITH IDTC BRANDING) -->
    <!-- ============================================================================== -->
    <footer class="bg-primary-obsidian pt-20 pb-12 border-t border-border-subtle text-slate-400 text-xs">
        <div class="container-custom">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 mb-16">
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-3.5 select-none" aria-label="IDTC">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-orange via-orange-600 to-orange-700 flex items-center justify-center text-white shadow-lg shadow-primary-orange/20 border border-orange-400/30 shrink-0">
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="2.5" fill="currentColor" stroke="none"/>
                                <circle cx="12" cy="12" r="5" stroke-dasharray="2 2" stroke-width="1.2"/>
                                <path d="M6.5 6.5L9.5 9.5M17.5 6.5L14.5 9.5M6.5 17.5L9.5 14.5M17.5 17.5L14.5 14.5" stroke-width="1.8"/>
                                <circle cx="5" cy="5" r="2.2" stroke-width="1.8"/>
                                <circle cx="19" cy="5" r="2.2" stroke-width="1.8"/>
                                <circle cx="5" cy="19" r="2.2" stroke-width="1.8"/>
                                <circle cx="19" cy="19" r="2.2" stroke-width="1.8"/>
                            </svg>
                        </div>
                        <div class="flex flex-col justify-center">
                            <span class="font-heading font-black text-lg text-white block leading-none">IDTC</span>
                            <span class="text-[10px] font-semibold text-slate-400 block tracking-wider uppercase mt-1 leading-tight">PT Jogja Creative Production</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                        Indonesia Drone Training Center — Layanan pelatihan drone profesional oleh PT Jogja Creative Production.
                    </p>
                </div>

                <div>
                    <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-4">Navigasi</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#expertise" class="hover:text-primary-orange">Project & Training</a></li>
                        <li><a href="#certification" class="hover:text-primary-orange">Certification</a></li>
                        <li><a href="#gallery" class="hover:text-primary-orange">Gallery</a></li>
                        <li><a href="#news" class="hover:text-primary-orange" data-i18n="nav-news">Artikel</a></li>
                        <li><a href="#credentials" class="hover:text-primary-orange">Credentials</a></li>
                        <li><a href="#faq" class="hover:text-primary-orange">FAQ</a></li>
                    </ul>
                </div>

                <div class="lg:col-span-2 space-y-3">
                    <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-4">Informasi Kontak Official</h4>
                    <p class="text-xs flex items-center gap-2">
                        <span class="text-primary-orange font-semibold">Perusahaan:</span>
                        <span class="text-white font-medium">PT Jogja Creative Production</span>
                    </p>
                    <p class="text-xs flex items-center gap-2">
                        <span class="text-primary-orange font-semibold">Alamat:</span>
                        <span class="text-white font-medium">Yogyakarta, Indonesia</span>
                    </p>
                    <p class="text-xs flex items-center gap-2">
                        <span class="text-primary-orange font-semibold">WhatsApp:</span>
                        <a href="<?php echo htmlspecialchars($contactWhatsappLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-slate-200 hover:text-primary-orange underline"><?php echo htmlspecialchars($contactWhatsapp, ENT_QUOTES, 'UTF-8'); ?></a>
                    </p>
                    <p class="text-xs flex items-center gap-2">
                        <span class="text-primary-orange font-semibold">Instagram:</span>
                        <a href="https://www.instagram.com/<?php echo htmlspecialchars($instagramHandle, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-slate-200 hover:text-primary-orange underline"><?php echo htmlspecialchars($contactInstagram, ENT_QUOTES, 'UTF-8'); ?></a>
                    </p>
                </div>
            </div>

            <div class="pt-8 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center text-[11px] text-slate-500 gap-4">
                <p>Master The Sky — Indonesia Drone Training Center</p>
            </div>
        </div>
    </footer>


    <!-- ============================================================================== -->
    <!-- DIRECT CASE STUDY VIEWER MODAL (NO REGISTRATION REQUIRED) -->
    <!-- ============================================================================== -->
    <div id="case-study-modal" class="fixed inset-0 bg-primary-obsidian/90 backdrop-blur-lg z-50 items-center justify-center p-4 hidden">
        <div class="bg-dark-surface border border-border-subtle rounded-xl w-full max-w-3xl overflow-hidden shadow-2xl relative max-h-[90vh] flex flex-col">
            
            <!-- Header -->
            <div class="p-6 border-b border-border-subtle flex justify-between items-center bg-dark-elevated">
                <div>
                    <span id="cs-modal-category" class="text-[10px] font-extrabold uppercase tracking-widest text-primary-orange block mb-1">CATEGORY</span>
                    <h3 id="cs-modal-title" class="text-2xl font-black text-white uppercase">Project Case Study</h3>
                    <p id="cs-modal-subtitle" class="text-xs font-semibold text-slate-400">Subtitle</p>
                </div>
                <button onclick="closeCaseStudyModal()" class="text-slate-400 hover:text-white p-2 rounded-lg bg-black/40 hover:bg-black/80">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Body Content -->
            <div class="p-6 overflow-y-auto space-y-6">
                <!-- Case Study Image -->
                <div class="relative h-64 rounded-lg overflow-hidden border border-white/10">
                    <img id="cs-modal-image" src="" alt="Case Study Image" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-primary-obsidian/80 via-transparent to-transparent"></div>
                </div>

                <!-- Description -->
                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-1">DESKRIPSI PROYEK</h4>
                    <p id="cs-modal-desc" class="text-xs text-slate-300 leading-relaxed"></p>
                </div>

                <!-- Metrics Grid -->
                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-2">METRIK UTAMA</h4>
                    <div id="cs-modal-metrics" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <!-- Details breakdown -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div class="bg-dark-elevated border border-border-subtle p-4 rounded-lg">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-primary-orange block mb-1">TUJUAN PROYEK</span>
                        <p id="cs-modal-objective" class="text-xs text-slate-300 leading-relaxed"></p>
                    </div>

                    <div class="bg-dark-elevated border border-border-subtle p-4 rounded-lg">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-primary-orange block mb-1">TEKNOLOGI & FLEET</span>
                        <p id="cs-modal-tech" class="text-xs text-slate-300 leading-relaxed"></p>
                    </div>

                    <div class="bg-dark-elevated border border-border-subtle p-4 rounded-lg">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-primary-orange block mb-1">ALUR WORKFLOW</span>
                        <p id="cs-modal-workflow" class="text-xs text-slate-300 leading-relaxed"></p>
                    </div>

                    <div class="bg-dark-elevated border border-border-subtle p-4 rounded-lg">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-400 block mb-1">HASIL AKHIR & IMPACT</span>
                        <p id="cs-modal-result" class="text-xs text-slate-300 leading-relaxed"></p>
                    </div>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="p-4 border-t border-border-subtle bg-dark-elevated flex justify-between items-center">
                <button onclick="closeCaseStudyModal()" class="btn-secondary text-xs py-2.5 px-6">
                    <span>Close Case Study</span>
                </button>
            </div>

        </div>
    </div>


    <!-- ============================================================================== -->
    <!-- ADMIN AUTHENTICATION MODAL -->
    <!-- ============================================================================== -->
    <div id="admin-secret-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-[#111111]/90 backdrop-blur-md p-4">
        <div class="w-full max-w-md rounded-2xl border border-[#FF5E00]/60 bg-[#111111] p-6 shadow-[0_0_40px_rgba(255,94,0,0.25)] admin-modal-panel">
            <div class="mb-5 text-center">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[#FF5E00]/10 border border-[#FF5E00]/30 text-[#FF5E00] mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#FF5E00]">Admin Secure Portal</p>
                <h3 class="mt-1 text-2xl font-black uppercase tracking-tight text-white">LOGIN ADMIN STUDIO</h3>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="admin-password-input" class="mb-2 block text-[10px] font-bold uppercase tracking-[0.2em] text-slate-300">Password</label>
                    <input id="admin-password-input" type="password" placeholder="Masukkan password" class="w-full rounded-xl border border-white/10 bg-[#181818] px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-[#FF5E00] focus:outline-none focus:ring-2 focus:ring-[#FF5E00]/30" autocomplete="off" />
                    <p id="admin-password-error" class="mt-2 hidden text-xs font-medium text-red-400">Password Salah, Akses Ditolak.</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button id="admin-cancel-btn" type="button" class="flex-1 rounded-xl border border-white/10 bg-transparent px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-white/20 hover:bg-white/5">Batal</button>
                    <button id="admin-login-btn" type="button" class="flex-1 rounded-xl bg-[#FF5E00] px-4 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-[0_10px_30px_rgba(255,94,0,0.35)] transition hover:bg-[#ff741a]">Login Admin</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- CONTENT STUDIO OVERLAY / PORTAL SHELL -->
    <!-- ============================================================================== -->
    <div id="admin-portal-view" class="admin-portal-shell fixed inset-0 z-[70] hidden overflow-y-auto bg-[#FDFDFB] text-[#111111]">
        <div class="mx-auto max-w-[1500px] px-4 py-4 sm:px-6 lg:px-8">
            <div class="admin-panel-surface rounded-[30px] border border-[#D8C9A0] bg-white/90 shadow-2xl backdrop-blur-md">
                
                <!-- Studio Header Bar -->
                <header class="flex flex-col gap-4 border-b border-[#E8E1D5] px-5 py-4 sm:px-8 lg:flex-row lg:items-center lg:justify-between bg-white/60">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#D8C9A0] bg-[#FF5E00] text-xl font-black text-white shadow-md">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#FF5E00]">IDTC</p>
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-800 uppercase tracking-wider">Session Active</span>
                            </div>
                            <h1 class="text-xl font-black uppercase tracking-tight text-[#111111]">CONTENT STUDIO</h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-sm font-medium text-[#3F3F3F]">Total Artikel: <span id="studio-total-count" class="font-bold text-[#111111]">(0 Terdaftar)</span></p>
                        <button id="btn-new-article-mode" type="button" class="rounded-xl border border-[#111111] bg-[#111111] px-4 py-2.5 text-xs font-black uppercase tracking-[0.18em] text-white transition hover:bg-[#252525] flex items-center gap-2">
                            <span>+ ARTIKEL BARU</span>
                        </button>
                        <button id="btn-admin-logout" type="button" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-red-600 transition hover:bg-red-100 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span>LOGOUT</span>
                        </button>
                    </div>
                </header>

                <main class="px-5 py-5 sm:px-8 lg:px-10 space-y-8">
                    
                    <!-- ============================================================ -->
                    <!-- ARTICLE FORM SECTION (TULIS / EDIT ARTIKEL) -->
                    <!-- ============================================================ -->
                    <div id="article-form-card" class="rounded-[26px] border border-[#E8E1D5] bg-[#FBFAF7] p-4 sm:p-6 lg:p-8 shadow-sm">
                        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-[#E8E1D5] pb-4">
                            <div>
                                <span id="form-mode-badge" class="rounded-md bg-[#FF5E00]/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-[#FF5E00]">MODE DRAFT</span>
                                <h2 id="form-mode-title" class="mt-1 text-2xl font-black uppercase tracking-tight text-[#111111] sm:text-3xl">TULIS ARTIKEL BARU.</h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <button id="btn-preview-draft" type="button" class="rounded-xl border border-[#D7CBA7] bg-white px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-[#111111] transition hover:border-[#111111] flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-[#FF5E00]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <span>Preview Tampilan</span>
                                </button>
                                <button id="admin-close-view-btn" type="button" class="rounded-full border border-[#111111]/15 bg-white px-4 py-2 text-[10px] font-black uppercase tracking-[0.22em] text-[#111111] transition hover:border-[#111111]">TUTUP STUDIO</button>
                            </div>
                        </div>

                        <!-- Form Input Hidden ID -->
                        <input type="hidden" id="article-form-id" value="0" />

                        <div class="grid gap-5 lg:grid-cols-2">
                            <div class="space-y-5">
                                <div>
                                    <label for="article-form-title" class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">JUDUL ARTIKEL <span class="text-red-500">*</span></label>
                                    <input id="article-form-title" type="text" placeholder="Contoh: IDTC Meluncurkan Pelatihan Sertifikasi Pilot Drone Industri 2026" class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 text-sm text-[#111111] placeholder:text-[#9A938D] focus:border-[#B78A2D] focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20" />
                                </div>

                                <div>
                                    <label for="article-form-author" class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">PENULIS / TIM <span class="text-red-500">*</span></label>
                                    <input id="article-form-author" type="text" value="Tim Media IDTC" class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 text-sm text-[#111111] focus:border-[#B78A2D] focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20" />
                                </div>

                                <div>
                                    <label for="article-form-category" class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">KATEGORI <span class="text-red-500">*</span></label>
                                    <select id="article-form-category" class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 text-sm text-[#111111] focus:border-[#B78A2D] focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20">
                                        <option value="Berita" selected>Berita & Pengumuman</option>
                                        <option value="Pelatihan">Pelatihan & Sertifikasi</option>
                                        <option value="Teknologi">Teknologi & Inovasi Drone</option>
                                        <option value="Event">Event & Workshop</option>
                                        <option value="Ops Lapangan">Operasional & Inspeksi Lapangan</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                    <div id="article-form-custom-category-wrap" class="mt-3 hidden">
                                        <label for="article-form-custom-category" class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">TULIS KATEGORI DI SINI <span class="text-red-500">*</span></label>
                                        <input id="article-form-custom-category" type="text" maxlength="100" placeholder="Tulis kategori di sini" class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 text-sm text-[#111111] placeholder:text-[#9A938D] focus:border-[#B78A2D] focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20" />
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <div>
                                    <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">GAMBAR UTAMA ARTIKEL</label>
                                    <div class="rounded-xl border border-dashed border-[#D7CBA7] bg-[#F7F4EE] p-4">
                                        <label for="article-form-image-file" class="mb-1 block text-[11px] font-bold text-[#111111]">Upload Berkas Gambar (Maks 5MB)</label>
                                        <input id="article-form-image-file" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" class="block w-full text-sm text-[#111111] file:mr-3 file:rounded-md file:border-0 file:bg-[#111111] file:px-3 file:py-2 file:text-xs file:font-bold file:uppercase file:text-white" />
                                        
                                        <div class="relative my-3 flex items-center justify-center">
                                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[#E3DCCB]"></div></div>
                                            <span class="relative bg-[#F7F4EE] px-2 text-[10px] font-bold uppercase tracking-wider text-[#8A7B58]">ATAU</span>
                                        </div>

                                        <div>
                                            <label for="article-form-image-url" class="mb-1 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">URL Gambar Luar</label>
                                            <input id="article-form-image-url" type="text" placeholder="https://images.unsplash.com/photo-..." class="w-full rounded-lg border border-[#E3DCCB] bg-white px-3 py-2.5 text-sm text-[#111111] placeholder:text-[#9A938D] focus:border-[#B78A2D] focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20" />
                                        </div>

                                        <!-- Image Thumbnail Preview -->
                                        <div id="image-preview-wrap" class="mt-3 hidden">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-[#6A5B3A] mb-1">Preview Gambar:</p>
                                            <img id="image-preview-img" src="" alt="Preview" class="h-28 w-full rounded-lg object-cover border border-[#E3DCCB]" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 space-y-5">
                            <div>
                                <label for="article-form-summary" class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">RINGKASAN SINGKAT (SUMMRAY)</label>
                                <textarea id="article-form-summary" rows="3" placeholder="Tuliskan 2-3 kalimat ringkasan yang menarik untuk ditampilkan pada kartu artikel..." class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 text-sm text-[#111111] placeholder:text-[#9A938D] focus:border-[#B78A2D] focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20"></textarea>
                            </div>

                            <div>
                                <label for="article-form-content" class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">ISI ARTIKEL LENGKAP <span class="text-red-500">*</span></label>
                                <textarea id="article-form-content" rows="12" placeholder="Tuliskan paragraf dan narasi lengkap artikel di sini..." class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 text-sm text-[#111111] placeholder:text-[#9A938D] focus:border-[#B78A2D] focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20"></textarea>
                            </div>
                        </div>

                        <!-- Status & Schedule Setup -->
                        <div class="mt-8 rounded-xl border border-[#E8E1D5] bg-white p-4 sm:p-6">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="article-form-status" class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">STATUS PUBLIKASI</label>
                                    <select id="article-form-status" class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 text-sm font-bold text-[#111111] focus:border-[#B78A2D] focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20">
                                        <option value="draft" selected>Draft (Disimpan Internal, Belum Tayang)</option>
                                        <option value="scheduled">Terjadwal (Otomatis Tayang Pada Waktu Ditentukan)</option>
                                        <option value="published">Publish Sekarang (Langsung Tayang di Website)</option>
                                    </select>
                                </div>

                                <div id="scheduled-datetime-wrap" class="hidden">
                                    <label for="article-form-scheduled-at" class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-[#6A5B3A]">TANGGAL & WAKTU PUBLISH (WIB)</label>
                                    <input id="article-form-scheduled-at" type="datetime-local" class="w-full rounded-xl border border-[#E3DCCB] bg-white px-4 py-3 text-sm font-bold text-[#111111] focus:border-[#B78A2D] focus:outline-none focus:ring-2 focus:ring-[#B78A2D]/20" />
                                    <p class="mt-1 text-[11px] text-[#6A5B3A]">Sistem akan mempublikasikan artikel secara otomatis saat jam tersebut tercapai (WIB).</p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Action Buttons -->
                        <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-[#E8E1D5] pt-5">
                            <!-- Left: Cancel / Reset Buttons -->
                            <div class="flex items-center gap-2">
                                <button id="btn-cancel-edit" type="button" class="hidden rounded-xl border border-slate-300 bg-white px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-700 transition hover:bg-slate-50">Batal Edit</button>
                                <button id="btn-unpublish-article" type="button" class="hidden rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs font-bold uppercase tracking-wider text-amber-800 transition hover:bg-amber-100 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    <span>Tarik dari Website (Set Draft)</span>
                                </button>
                                <button id="btn-delete-article" type="button" class="hidden rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-xs font-bold uppercase tracking-wider text-red-600 transition hover:bg-red-100 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    <span>Hapus Artikel</span>
                                </button>
                            </div>

                            <!-- Right: Submit Action Buttons -->
                            <div class="flex items-center gap-3">
                                <button id="btn-save-draft" type="button" class="rounded-xl border border-[#111111] bg-white px-5 py-3 text-xs font-black uppercase tracking-[0.18em] text-[#111111] transition hover:bg-slate-100">SIMPAN DRAFT</button>
                                <button id="btn-schedule-article" type="button" class="rounded-xl border border-amber-600 bg-amber-600 px-5 py-3 text-xs font-black uppercase tracking-[0.18em] text-white transition hover:bg-amber-700">JADWALKAN</button>
                                <button id="btn-publish-now" type="button" class="rounded-xl border border-[#FF5E00] bg-[#FF5E00] px-6 py-3 text-xs font-black uppercase tracking-[0.18em] text-white shadow-lg shadow-[#FF5E00]/30 transition hover:bg-[#ff741a]">PUBLISH SEKARANG</button>
                            </div>
                        </div>

                    </div>

                    <!-- ============================================================ -->
                    <!-- ARTICLE SCHEDULE CALENDAR -->
                    <!-- ============================================================ -->
                    <section id="article-calendar-section" class="rounded-[26px] border border-[#E8E1D5] bg-[#FBFAF7] p-4 sm:p-6 lg:p-8 shadow-sm">
                        <div class="mb-6 flex flex-col gap-4 border-b border-[#E8E1D5] pb-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-xl font-black uppercase tracking-tight text-[#111111]">KALENDER JADWAL POSTING</h3>
                                <p class="text-xs text-[#6A5B3A]">Atur dan pantau artikel terjadwal berdasarkan waktu WIB.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button id="calendar-today-btn" type="button" class="rounded-lg border border-[#D7CBA7] bg-white px-3 py-2 text-[10px] font-black uppercase tracking-wider text-[#111111] transition hover:border-[#111111]">Hari Ini</button>
                                <button id="calendar-prev-btn" type="button" aria-label="Bulan sebelumnya" class="calendar-nav-btn rounded-lg border border-[#D7CBA7] bg-white px-3 py-2 text-lg leading-none text-[#111111] transition hover:border-[#111111]">&lsaquo;</button>
                                <button id="calendar-next-btn" type="button" aria-label="Bulan berikutnya" class="calendar-nav-btn rounded-lg border border-[#D7CBA7] bg-white px-3 py-2 text-lg leading-none text-[#111111] transition hover:border-[#111111]">&rsaquo;</button>
                            </div>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(260px,0.65fr)]">
                            <div>
                                <div class="mb-4 flex items-center justify-between">
                                    <h4 id="calendar-month-label" class="text-lg font-black uppercase tracking-tight text-[#111111]">BULAN INI</h4>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8A7B58]">Asia/Jakarta</span>
                                </div>
                                <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-black uppercase tracking-wider text-[#8A7B58] sm:gap-2">
                                    <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                                </div>
                                <div id="article-calendar-grid" class="mt-2 grid grid-cols-7 gap-1 sm:gap-2"></div>
                            </div>
                            <aside class="rounded-xl border border-[#E8E1D5] bg-white p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-[#6A5B3A]">AGENDA BULAN INI</h4>
                                    <span id="calendar-event-count" class="rounded-full bg-[#F4F0E6] px-2 py-1 text-[10px] font-bold text-[#6A5B3A]">0</span>
                                </div>
                                <div id="calendar-agenda-list" class="space-y-2 text-xs text-[#3F3F3F]"></div>
                            </aside>
                        </div>
                    </section>

                    <!-- ============================================================ -->
                    <!-- ARTICLE MANAGEMENT TABLE LIST (KELOLA ARTIKEL) -->
                    <!-- ============================================================ -->
                    <div class="rounded-[26px] border border-[#E8E1D5] bg-white p-4 sm:p-6 lg:p-8 shadow-sm">
                        
                        <!-- Filter & Search Toolbar -->
                        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-[#E8E1D5] pb-4">
                            <div>
                                <h3 class="text-xl font-black uppercase tracking-tight text-[#111111]">DAFTAR KELOLA ARTIKEL</h3>
                                <p class="text-xs text-[#6A5B3A]">Filter dan kelola status artikel yang tersimpan di sistem.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Status Filter Tabs -->
                                <div class="inline-flex rounded-xl bg-[#F4F0E6] p-1 text-xs font-bold">
                                    <button id="filter-btn-all" onclick="filterAdminArticles('all')" class="rounded-lg px-3 py-1.5 text-[#111111] bg-white shadow-sm transition">Semua</button>
                                    <button id="filter-btn-draft" onclick="filterAdminArticles('draft')" class="rounded-lg px-3 py-1.5 text-[#6A5B3A] hover:text-[#111111] transition">Draft</button>
                                    <button id="filter-btn-scheduled" onclick="filterAdminArticles('scheduled')" class="rounded-lg px-3 py-1.5 text-[#6A5B3A] hover:text-[#111111] transition">Terjadwal</button>
                                    <button id="filter-btn-published" onclick="filterAdminArticles('published')" class="rounded-lg px-3 py-1.5 text-[#6A5B3A] hover:text-[#111111] transition">Published</button>
                                </div>

                                <!-- Search Input -->
                                <div class="relative min-w-[200px]">
                                    <input id="admin-search-input" type="text" placeholder="Cari judul..." class="w-full rounded-xl border border-[#E3DCCB] bg-[#FBFAF7] pl-8 pr-3 py-1.5 text-xs text-[#111111] focus:border-[#B78A2D] focus:outline-none" />
                                    <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Articles Table Container -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-[#E8E1D5] bg-[#FBFAF7] text-xs font-black uppercase tracking-wider text-[#6A5B3A]">
                                        <th class="px-4 py-3">Cover</th>
                                        <th class="px-4 py-3">Judul Artikel & Metadata</th>
                                        <th class="px-4 py-3">Kategori</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Waktu WIB</th>
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-articles-table-body" class="divide-y divide-[#E8E1D5]">
                                    <!-- Dynamic rows loaded via JS -->
                                </tbody>
                            </table>

                            <!-- Empty State View -->
                            <div id="admin-empty-state" class="hidden flex flex-col items-center justify-center text-center py-12">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#F4F0E6] text-2xl text-[#8A7B58] mb-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                                </div>
                                <h4 class="text-base font-bold text-[#111111]">Belum Ada Artikel</h4>
                                <p class="text-xs text-[#7B7B7B] max-w-sm mt-1">Tidak ada artikel yang sesuai dengan kriteria atau filter yang dipilih.</p>
                            </div>
                        </div>

                    </div>

                </main>
            </div>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- PUBLIC ARTICLE READER MODAL -->
    <!-- ============================================================================== -->
    <div id="public-article-modal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/80 backdrop-blur-md p-4 overflow-y-auto">
        <div class="w-full max-w-4xl rounded-2xl border border-white/10 bg-[#141414] text-white p-6 sm:p-8 shadow-2xl relative my-8">
            <button onclick="closePublicArticleModal()" class="absolute top-4 right-4 h-9 w-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span id="pub-modal-category" class="rounded-md bg-primary-orange/20 border border-primary-orange/40 px-3 py-1 text-xs font-bold uppercase tracking-wider text-primary-orange">Kategori</span>
                    <span id="pub-modal-date" class="text-xs font-medium text-slate-400">3 Sep 2026 • 08:00 WIB</span>
                </div>

                <h1 id="pub-modal-title" class="text-2xl sm:text-3xl font-black uppercase tracking-tight text-white leading-snug">Judul Artikel Lengkap</h1>

                <div class="flex items-center gap-3 text-xs text-slate-400 border-b border-white/10 pb-4">
                    <span>Oleh: <strong id="pub-modal-author" class="text-white">Admin IDTC</strong></span>
                </div>

                <div id="pub-modal-image-wrap" class="rounded-xl overflow-hidden max-h-[400px] border border-white/10">
                    <img id="pub-modal-image" src="" alt="Cover" class="w-full h-full object-cover" />
                </div>

                <div id="pub-modal-summary-box" class="rounded-xl bg-white/5 border border-white/10 p-4 text-sm text-slate-200 font-medium italic leading-relaxed">
                    <!-- Summary content -->
                </div>

                <div id="pub-modal-content" class="text-slate-200 text-sm sm:text-base leading-relaxed space-y-4 pt-2 whitespace-pre-line">
                    <!-- Article Full Body Text -->
                </div>
            </div>

            <div class="mt-8 pt-4 border-t border-white/10 flex justify-end">
                <button onclick="closePublicArticleModal()" class="btn-secondary text-xs py-2.5 px-6 font-bold">
                    <span>Tutup Artikel</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- ADMIN PREVIEW MODAL -->
    <!-- ============================================================================== -->
    <div id="admin-preview-modal" class="fixed inset-0 z-[95] hidden items-center justify-center bg-black/85 backdrop-blur-md p-4 overflow-y-auto">
        <div class="w-full max-w-3xl rounded-2xl border border-[#D8C9A0] bg-[#FBFAF7] text-[#111111] p-6 sm:p-8 shadow-2xl relative my-8">
            <div class="flex items-center justify-between border-b border-[#E8E1D5] pb-4 mb-4">
                <div class="flex items-center gap-2">
                    <span class="rounded-md bg-[#FF5E00] px-2.5 py-1 text-[10px] font-black uppercase text-white">PREVIEW MODE</span>
                    <span id="prev-modal-status-badge" class="rounded-md bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-800 uppercase">Draft</span>
                </div>
                <button onclick="closeAdminPreviewModal()" class="text-xs font-bold uppercase tracking-wider text-slate-600 hover:text-black">Tutup Preview</button>
            </div>

            <div class="space-y-4">
                <span id="prev-modal-category" class="text-xs font-black uppercase tracking-wider text-[#FF5E00]">Kategori</span>
                <h2 id="prev-modal-title" class="text-2xl font-black uppercase text-[#111111]">Judul Preview</h2>
                <p id="prev-modal-meta" class="text-xs text-slate-500">Penulis: Admin • Dibuat: Now</p>
                <img id="prev-modal-image" src="" class="h-64 w-full rounded-xl object-cover border border-[#E8E1D5] hidden" />
                <div id="prev-modal-summary" class="rounded-lg bg-[#F4F0E6] p-3 text-xs italic font-medium"></div>
                <div id="prev-modal-content" class="text-sm leading-relaxed whitespace-pre-line text-[#222]"></div>
            </div>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- UNIVERSAL CONFIRMATION DIALOG MODAL -->
    <!-- ============================================================================== -->
    <div id="admin-confirm-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-2xl border border-white/20 bg-[#181818] p-6 text-white shadow-2xl">
            <div class="flex items-center gap-3 mb-4">
                <div id="confirm-icon-wrap" class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h4 id="confirm-modal-title" class="text-lg font-bold">Konfirmasi Tindakan</h4>
                    <p id="confirm-modal-subtitle" class="text-xs text-slate-400">IDTC Content Studio System</p>
                </div>
            </div>

            <p id="confirm-modal-message" class="text-sm text-slate-300 mb-6 leading-relaxed">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>

            <div class="flex items-center justify-end gap-3">
                <button id="confirm-modal-cancel" type="button" class="rounded-xl border border-white/10 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-white/5">Batal</button>
                <button id="confirm-modal-action" type="button" class="rounded-xl bg-red-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-red-700 shadow-md">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <div id="chatbot-widget">
        <button id="chat-toggle-btn" class="w-14 h-14 bg-gradient-to-r from-primary-orange to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-full flex items-center justify-center shadow-2xl shadow-primary-orange/40 transition-all hover:scale-105 border border-white/20" aria-label="Open AI Assistant Chat">
            <!-- Radar Scanner AI Drone Icon -->
            <svg class="w-7 h-7 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                <circle cx="12" cy="12" r="7" stroke-dasharray="3 3"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
            </svg>
        </button>
    </div>

    <div id="chat-window-container" class="chat-hidden" style="width:min(360px, calc(100vw - 2rem)) !important; max-width:calc(100vw - 2rem) !important; height:min(450px, calc(100dvh - 7.5rem)) !important; max-height:calc(100dvh - 7.5rem) !important; right:1rem !important; bottom:5.25rem !important;">
        <div class="bg-dark-surface border-b border-border-subtle p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary-orange/20 border border-primary-orange text-primary-orange flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div>
                    <h4 class="text-white font-bold text-xs leading-tight">IDTC Assistant</h4>
                    <p class="text-[10px] text-primary-orange flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-orange inline-block animate-pulse"></span>
                        <span>AI Assistant</span>
                    </p>
                </div>
            </div>
            <button id="close-chat-btn" class="text-slate-400 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div id="chat-messages-body" class="flex-1 p-4 overflow-y-auto bg-primary-obsidian space-y-3">
            <div class="flex justify-start mb-3">
                <div class="chat-bubble-ai max-w-[82%] p-3 text-xs">
                    Halo! 👋<br>
                    Saya IDTC Assistant dari PT Jogja Creative Production. Ada yang ingin Anda tanyakan seputar pelatihan drone atau layanan aviasi kami?
                </div>
            </div>
        </div>

        <div class="p-3 bg-dark-surface border-t border-border-subtle">
            <form id="chat-form-input" class="flex gap-2">
                <input type="text" id="chat-text-field" placeholder="Ketik pertanyaan Anda..." autocomplete="off" class="flex-1 bg-primary-obsidian border border-slate-700 text-white rounded px-4 py-2 text-xs focus:outline-none focus:border-primary-orange">
                <button type="submit" class="w-9 h-9 bg-primary-orange text-white rounded flex items-center justify-center shrink-0 hover:bg-orange-600">
                    <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Dynamic Database Data Injection -->
    <script>
        window.IDTC_DB_SERVICES = <?php echo json_encode($dynamicServices); ?>;
        window.IDTC_DB_TRAINING = <?php echo json_encode($dynamicTraining); ?>;
        window.IDTC_DB_CMS = <?php echo json_encode($dynamicCms); ?>;
        window.IDTC_WHATSAPP_LINK = <?php echo json_encode($contactWhatsappLink); ?>;
        window.IDTC_ADMIN_LOGGED_IN = <?php echo json_encode(!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true); ?>;
    </script>
    
    <!-- Notification system (toast/confirm/inline) -->
    <script src="js/notify.js"></script>
    <!-- Motion Controller & Main Engine -->
    <script src="js/animations.js"></script>
    <script src="js/main.js?v=<?php echo filemtime(__DIR__ . '/js/main.js'); ?>"></script>
    <script src="js/cms-content.js?v=<?php echo filemtime(__DIR__ . '/js/cms-content.js'); ?>"></script>
    <script src="js/live-update.js"></script>
    <!-- Jam Indonesia (WIB / WITA / WIT) -->
    <script src="js/indonesia-clocks.js"></script>
</body>
</html>
