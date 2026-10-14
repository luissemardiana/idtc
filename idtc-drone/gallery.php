<?php
require_once __DIR__ . '/config.php';

/**
 * IDTC Gallery Detail Page
 * 
 * Reads ?category= parameter and renders the appropriate photo gallery
 * with masonry layout and fullscreen lightbox.
 * 
 * Data is stored in PHP arrays for easy future migration to database.
 */

// ============================================================================
// GALLERY DATA — Easy to extend or migrate to DB
// ============================================================================
$galleryData = [
    'training' => [
        'title' => 'TRAINING',
        'subtitle' => 'Professional Drone Training',
        'description' => 'Dokumentasi kegiatan pelatihan drone profesional, latihan penerbangan praktis, aktivitas kelas, dan operasi lapangan oleh IDTC.',
        'photos' => [
            ['src' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Outdoor Flight Practice', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=800&auto=format&fit=crop', 'caption' => 'Ground School Briefing', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1473968512647-3e447244af8f?q=80&w=800&auto=format&fit=crop', 'caption' => 'Aerial Maneuver Training', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1506947411487-a56738571f67?q=80&w=800&auto=format&fit=crop', 'caption' => 'Pre-Flight Checklist', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Mapping Flight Session', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop', 'caption' => 'Equipment Setup', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1519074069444-1ba4edd16be1?q=80&w=800&auto=format&fit=crop', 'caption' => 'Team Coordination', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop', 'caption' => 'Classroom Theory', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Field Operations', 'size' => 'large'],
        ]
    ],
    'projects' => [
        'title' => 'PROJECTS',
        'subtitle' => 'Selected Project Documentation',
        'description' => 'Rekam jejak visual dari proyek-proyek profesional yang telah diselesaikan oleh tim IDTC.',
        'photos' => [
            ['src' => 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Drone Mapping Project', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=800&auto=format&fit=crop', 'caption' => 'Agricultural Survey', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1473968512647-3e447244af8f?q=80&w=800&auto=format&fit=crop', 'caption' => 'Aerial Inspection', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=800&auto=format&fit=crop', 'caption' => 'Topographic Mapping', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Industrial Survey', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1506947411487-a56738571f67?q=80&w=800&auto=format&fit=crop', 'caption' => 'Precision Agriculture', 'size' => 'small'],
        ]
    ],
    'events' => [
        'title' => 'EVENTS',
        'subtitle' => 'Company Events & Activities',
        'description' => 'Dokumentasi acara, seminar, workshop, dan kegiatan perusahaan PT Drone Trainee Center.',
        'photos' => [
            ['src' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Annual Drone Expo', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1519074069444-1ba4edd16be1?q=80&w=800&auto=format&fit=crop', 'caption' => 'Workshop Session', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop', 'caption' => 'Corporate Seminar', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=800&auto=format&fit=crop', 'caption' => 'Outdoor Demo Day', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Exhibition Stand', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1473968512647-3e447244af8f?q=80&w=800&auto=format&fit=crop', 'caption' => 'Community Meetup', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=800&auto=format&fit=crop', 'caption' => 'Award Ceremony', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop', 'caption' => 'Press Conference', 'size' => 'small'],
        ]
    ],
    'drone-operations' => [
        'title' => 'DRONE OPERATIONS',
        'subtitle' => 'Field Operations & Missions',
        'description' => 'Dokumentasi operasi drone di lapangan, misi pemetaan, inspeksi, dan kegiatan penerbangan profesional.',
        'photos' => [
            ['src' => 'https://images.unsplash.com/photo-1473968512647-3e447244af8f?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Multi-Drone Formation', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=800&auto=format&fit=crop', 'caption' => 'Altitude Operation', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=800&auto=format&fit=crop', 'caption' => 'Precision Landing', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop', 'caption' => 'Industrial Inspection', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Night Vision Mission', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1506947411487-a56738571f67?q=80&w=800&auto=format&fit=crop', 'caption' => 'Search & Rescue Drill', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?q=80&w=800&auto=format&fit=crop', 'caption' => 'Agricultural Spray', 'size' => 'medium'],
        ]
    ],
    'certifications' => [
        'title' => 'CERTIFICATIONS',
        'subtitle' => 'Certification & Qualification',
        'description' => 'Dokumentasi proses sertifikasi, evaluasi kompetensi, dan penyerahan sertifikat pilot drone IDTC.',
        'photos' => [
            ['src' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Certificate Ceremony', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=800&auto=format&fit=crop', 'caption' => 'Written Exam', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=800&auto=format&fit=crop', 'caption' => 'Practical Assessment', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop', 'caption' => 'Evaluation Panel', 'size' => 'medium'],
        ]
    ],
    'facilities' => [
        'title' => 'FACILITIES',
        'subtitle' => 'Training Facilities & Equipment',
        'description' => 'Fasilitas pelatihan, peralatan drone, simulator, dan ruang kelas yang digunakan oleh IDTC.',
        'photos' => [
            ['src' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Main Training Room', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1519074069444-1ba4edd16be1?q=80&w=800&auto=format&fit=crop', 'caption' => 'Control Station', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=800&auto=format&fit=crop', 'caption' => 'Drone Fleet', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1473968512647-3e447244af8f?q=80&w=800&auto=format&fit=crop', 'caption' => 'Open Field Arena', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Maintenance Workshop', 'size' => 'large'],
        ]
    ],
    'team-activities' => [
        'title' => 'TEAM ACTIVITIES',
        'subtitle' => 'Behind The Scenes',
        'description' => 'Dokumentasi aktivitas tim, koordinasi lapangan, dan momen di balik layar operasional IDTC.',
        'photos' => [
            ['src' => 'https://images.unsplash.com/photo-1519074069444-1ba4edd16be1?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Team Briefing', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?q=80&w=800&auto=format&fit=crop', 'caption' => 'Field Coordination', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop', 'caption' => 'Planning Session', 'size' => 'small'],
            ['src' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=800&auto=format&fit=crop', 'caption' => 'Team Photo', 'size' => 'medium'],
            ['src' => 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=1200&auto=format&fit=crop', 'caption' => 'Celebration', 'size' => 'large'],
            ['src' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop', 'caption' => 'Equipment Check', 'size' => 'small'],
        ]
    ],
];

// Folder dan foto dari admin adalah sumber utama. Jangan tampilkan foto contoh
// saat folder sudah ada tetapi fotonya telah dihapus dari admin.
$pdo = getDbConnection();
if ($pdo) {
    try {
        $galleryData = [];
        $folderDescriptions = [];
        try {
            foreach ($pdo->query("SELECT category, description FROM gallery_folders") as $folder) {
                $categoryKey = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$folder['category'])), '-');
                $folderDescriptions[$categoryKey] = trim((string)($folder['description'] ?? ''));
                $galleryData[$categoryKey] = [
                    'title' => strtoupper(preg_replace('/\s+/', ' ', str_replace(['-', '_'], ' ', (string)$folder['category']))),
                    'subtitle' => 'Dokumentasi IDTC',
                    'description' => $folderDescriptions[$categoryKey],
                    'photos' => [],
                    '_db' => true
                ];
            }
        } catch (PDOException $e) { /* Older database without gallery_folders description. */ }
        $stmt = $pdo->query("SELECT category, title, caption, image_url FROM gallery_items WHERE status = 'active' ORDER BY category, display_order ASC, id ASC");
        foreach ($stmt->fetchAll() as $item) {
            $categoryKey = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string)$item['category'])), '-');
            if (!isset($galleryData[$categoryKey])) $galleryData[$categoryKey] = ['title' => strtoupper(preg_replace('/\s+/', ' ', str_replace(['-', '_'], ' ', (string)$item['category']))), 'subtitle' => 'Gallery IDTC', 'description' => 'Gallery IDTC.', 'photos' => [], '_db' => true];
            if (!empty($folderDescriptions[$categoryKey])) $galleryData[$categoryKey]['description'] = $folderDescriptions[$categoryKey];
            $galleryData[$categoryKey]['photos'][] = ['src'=>$item['image_url'], 'caption'=>$item['caption'] ?: $item['title']];
        }
    } catch (PDOException $e) { /* static gallery fallback */ }
}

// Get category from URL
$category = isset($_GET['category']) ? $_GET['category'] : '';
$category = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($category)), '-'); // slug canonicalization

if (!isset($galleryData[$category])) {
    header('Location: index.php#gallery');
    exit;
}

$data = $galleryData[$category];
$photoCount = count($data['photos']);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IDTC Gallery — <?php echo htmlspecialchars($data['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($data['description']); ?>">

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
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Page entry animation */
        .gallery-page-enter {
            animation: galleryFadeIn 0.5s ease forwards;
        }
        @keyframes galleryFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .photo-stagger { opacity: 0; transform: translateY(15px); transition: opacity 0.4s ease, transform 0.4s ease; }
        .photo-stagger.visible { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body class="bg-primary-obsidian text-slate-100 font-body antialiased selection:bg-primary-orange selection:text-white">

    <!-- ============================================================================== -->
    <!-- GALLERY DETAIL PAGE -->
    <!-- ============================================================================== -->
    <div class="gallery-page-enter">

        <!-- Breadcrumb & Back Navigation -->
        <div class="bg-dark-surface border-b border-border-subtle">
            <div class="container-custom py-6 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-2 text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <a href="index.php#gallery" class="text-slate-400 hover:text-white transition-colors">Gallery</a>
                    <span class="text-slate-600">/</span>
                    <span class="text-primary-orange"><?php echo htmlspecialchars($data['title']); ?></span>
                </div>
                <a href="index.php#gallery" class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-slate-400 hover:text-white transition-colors uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Back to Gallery</span>
                </a>
            </div>
        </div>

        <!-- Category Header -->
        <section class="pt-20 pb-12">
            <div class="container-custom">
                <div class="badge-eyebrow" style="display:inline-flex; align-items:center; gap:8px; margin-bottom:12px;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    GALLERY / <?php echo htmlspecialchars($data['title']); ?>
                </div>
                <h1 class="text-4xl sm:text-6xl md:text-7xl font-black text-white uppercase tracking-tight leading-none mb-4">
                    <?php echo htmlspecialchars($data['title']); ?>
                </h1>
                <p class="text-slate-300 text-base sm:text-lg max-w-2xl leading-relaxed mb-3">
                    <?php echo htmlspecialchars($data['description']); ?>
                </p>
                <p class="text-xs font-extrabold uppercase tracking-widest text-primary-orange">
                    <?php echo $photoCount; ?> Photos
                </p>
            </div>
        </section>

        <!-- Divider -->
        <div class="container-custom">
            <div class="w-full h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
        </div>

        <!-- Photo Masonry Grid -->
        <section class="py-12 pb-28">
            <div class="container-custom">
                <div class="gallery-masonry" id="photo-grid">
                    <?php foreach ($data['photos'] as $i => $photo): ?>
                    <div class="gallery-masonry-item photo-stagger" data-index="<?php echo $i; ?>" onclick="openLightbox(<?php echo $i; ?>)">
                        <img src="<?php echo htmlspecialchars($photo['src']); ?>"
                             alt="<?php echo htmlspecialchars($photo['caption']); ?>"
                             loading="lazy">
                        <div class="photo-overlay">
                            <div class="view-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                            </div>
                            <?php if (!empty($photo['caption'])): ?>
                            <span class="photo-caption"><?php echo htmlspecialchars($photo['caption']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($data['photos'])): ?>
                    <div class="col-span-full rounded-2xl border border-white/10 bg-white/[.03] px-6 py-16 text-center text-slate-400">
                        Belum ada foto dalam folder ini.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Back to Gallery (Bottom) -->
        <section class="pb-20">
            <div class="container-custom text-center">
                <a href="index.php#gallery" class="inline-flex items-center gap-2.5 px-8 py-4 rounded-xl bg-white/5 border border-white/10 text-sm font-bold text-slate-300 hover:text-white hover:bg-white/10 transition-all uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Back to Gallery</span>
                </a>
            </div>
        </section>

    </div>

    <!-- ============================================================================== -->
    <!-- LIGHTBOX -->
    <!-- ============================================================================== -->
    <div class="gallery-lightbox" id="gallery-lightbox">
        <img id="lightbox-image" src="" alt="">
        <button class="lightbox-close" id="lightbox-close" aria-label="Close">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <button class="lightbox-prev" id="lightbox-prev" aria-label="Previous">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button class="lightbox-next" id="lightbox-next" aria-label="Next">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="lightbox-caption" id="lightbox-caption"></div>
    </div>

    <!-- ============================================================================== -->
    <!-- LIGHTBOX & SCROLL ANIMATION SCRIPT -->
    <!-- ============================================================================== -->
    <script>
    (function() {
        // Photo data from PHP
        const photos = <?php echo json_encode(array_map(function($p) {
            return ['src' => str_replace('w=800', 'w=1600', str_replace('w=400', 'w=1600', $p['src'])), 'caption' => $p['caption']];
        }, $data['photos'])); ?>;

        let currentIndex = 0;
        const lightbox = document.getElementById('gallery-lightbox');
        const lbImage = document.getElementById('lightbox-image');
        const lbCaption = document.getElementById('lightbox-caption');

        // Open
        window.openLightbox = function(index) {
            currentIndex = index;
            updateLightbox();
            lightbox.style.display = 'flex';
            requestAnimationFrame(() => { lightbox.classList.add('active'); });
            document.body.style.overflow = 'hidden';
        };

        // Close
        function closeLightbox() {
            lightbox.classList.remove('active');
            setTimeout(() => { lightbox.style.display = 'none'; }, 300);
            document.body.style.overflow = '';
        }

        // Update
        function updateLightbox() {
            if (photos[currentIndex]) {
                lbImage.src = photos[currentIndex].src;
                lbCaption.textContent = photos[currentIndex].caption || '';
            }
        }

        // Navigate
        function prevPhoto() { currentIndex = (currentIndex - 1 + photos.length) % photos.length; updateLightbox(); }
        function nextPhoto() { currentIndex = (currentIndex + 1) % photos.length; updateLightbox(); }

        // Event Listeners
        document.getElementById('lightbox-close').addEventListener('click', closeLightbox);
        document.getElementById('lightbox-prev').addEventListener('click', prevPhoto);
        document.getElementById('lightbox-next').addEventListener('click', nextPhoto);

        // Click background to close
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) closeLightbox();
        });

        // Keyboard
        document.addEventListener('keydown', function(e) {
            if (lightbox.style.display !== 'flex') return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevPhoto();
            if (e.key === 'ArrowRight') nextPhoto();
        });

        // Swipe (mobile)
        let touchStartX = 0;
        lightbox.addEventListener('touchstart', function(e) { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        lightbox.addEventListener('touchend', function(e) {
            const diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) prevPhoto(); else nextPhoto();
            }
        }, { passive: true });

        // Scroll stagger animation for photos
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => { entry.target.classList.add('visible'); }, i * 60);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.photo-stagger').forEach(el => observer.observe(el));
    })();
    </script>

</body>
</html>
