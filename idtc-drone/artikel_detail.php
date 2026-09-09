<?php
require_once __DIR__ . '/config.php';

$slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
$article = null;
$relatedArticles = [];

$pdo = getDbConnection();
if ($pdo && !empty($slug)) {
    autoPublishScheduledArticles($pdo);

    $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();

    if ($article) {
        $stmtRelated = $pdo->prepare(
            "SELECT title, slug, category, image_url, summary, published_at 
             FROM articles 
             WHERE status = 'published' AND id != ? 
             ORDER BY CASE WHEN display_order > 0 THEN display_order ELSE 999999999 END ASC, published_at DESC 
             LIMIT 3"
        );
        $stmtRelated->execute([$article['id']]);
        $relatedArticles = $stmtRelated->fetchAll();
    }
}

function fmtDate($value) {
    if (!$value) return '';
    $date = new DateTime($value, new DateTimeZone('Asia/Jakarta'));
    $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    return $date->format('j') . ' ' . $months[(int)$date->format('n')] . ' ' . $date->format('Y') . ' • ' . $date->format('H:i') . ' WIB';
}

function excerpt($text, $len = 120) {
    $text = trim($text);
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 3) . '...' : $text;
}

$pageTitle = $article ? htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') . ' | IDTC' : 'Artikel Tidak Ditemukan | IDTC';
$pageDesc  = $article ? htmlspecialchars(excerpt($article['summary'] ?: $article['content'], 160), ENT_QUOTES, 'UTF-8') : 'Artikel tidak ditemukan.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDesc; ?>">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ── Article Detail Page ── */
        .article-detail-page { min-height: 100vh; background: #080808; }

        .article-detail-header {
            position: sticky; top: 0; z-index: 40;
            border-bottom: 1px solid rgba(255,255,255,.08);
            padding: .9rem 0;
            background: rgba(8,8,8,.92);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        }

        /* Hero banner */
        .article-hero { position: relative; width: 100%; max-height: 480px; overflow: hidden; border-radius: 1rem; margin-bottom: 2.5rem; }
        .article-hero img { width: 100%; height: 480px; object-fit: cover; display: block; filter: brightness(.85); }
        .article-hero-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(180deg, transparent 40%, rgba(8,8,8,.95) 100%);
            pointer-events: none;
        }

        /* Meta bar */
        .article-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; margin-bottom: 1.75rem; }
        .article-meta-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .14em;
            padding: .35rem .75rem; border-radius: .375rem;
        }
        .badge-category { background: rgba(255,94,0,.12); color: #FF5E00; border: 1px solid rgba(255,94,0,.25); }
        .badge-date { background: rgba(255,255,255,.05); color: #999; border: 1px solid rgba(255,255,255,.08); }
        .badge-author { background: rgba(255,255,255,.05); color: #bbb; border: 1px solid rgba(255,255,255,.08); }

        /* Article title */
        .article-detail-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(2rem, 4.5vw, 3.5rem);
            font-weight: 900; line-height: 1.15; letter-spacing: -.02em;
            color: #fff; text-transform: uppercase; margin-bottom: 1.5rem;
        }

        /* Summary quote */
        .article-summary-quote {
            position: relative; padding: 1.25rem 1.5rem; margin-bottom: 2.5rem;
            border-left: 3px solid #FF5E00;
            background: rgba(255,94,0,.04); border-radius: 0 .5rem .5rem 0;
            font-size: 1.1rem; font-style: italic; color: #ccc; line-height: 1.7;
        }

        /* Body content */
        .article-body {
            font-size: 1.15rem; line-height: 1.9; color: #d4d4d4;
            white-space: pre-line; max-width: 52rem;
        }
        .article-body p { margin-bottom: 1.25rem; }

        /* Sidebar / Share card */
        .article-sidebar-card {
            position: sticky; top: 5rem;
            background: #111; border: 1px solid rgba(255,255,255,.08);
            border-radius: .75rem; padding: 1.5rem;
        }
        .article-sidebar-card h4 {
            font-size: .65rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: .18em; color: #FF5E00; margin-bottom: .75rem;
        }

        /* Related articles */
        .related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
        .related-card {
            display: flex; flex-direction: column;
            border: 1px solid rgba(255,255,255,.08); border-radius: .75rem;
            background: #111; overflow: hidden;
            transition: border-color .25s ease, transform .25s ease;
            text-decoration: none; color: inherit;
        }
        .related-card:hover { border-color: #FF5E00; transform: translateY(-3px); }
        .related-card img { width: 100%; height: 160px; object-fit: cover; }
        .related-card-body { padding: 1rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .related-card-body h3 { font-size: .9rem; font-weight: 800; color: #fff; line-height: 1.35; margin-bottom: .5rem; }
        .related-card-body p { font-size: .75rem; color: #999; line-height: 1.5; }

        /* Divider */
        .section-divider { border: none; border-top: 1px solid rgba(255,255,255,.08); margin: 3rem 0; }

        /* Back link */
        .back-link {
            display: inline-flex; align-items: center; gap: .5rem;
            font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em;
            color: #FF5E00; text-decoration: none; transition: opacity .2s;
        }
        .back-link:hover { opacity: .75; }

        /* 404 state */
        .article-not-found {
            min-height: 70vh; display: flex; flex-direction: column;
            align-items: center; justify-content: center; text-align: center; gap: 1rem;
        }
        .article-not-found h1 { font-size: 2.5rem; font-weight: 900; color: #fff; }
        .article-not-found p { color: #777; font-size: 1rem; }

        @media (max-width: 768px) {
            .article-hero img { height: 280px; }
            .article-body { font-size: 1.05rem; }
        }
    </style>
</head>
<body class="article-detail-page text-slate-100 antialiased">

    <!-- Sticky Header -->
    <header class="article-detail-header">
        <div class="container-custom flex items-center justify-between gap-4" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
            <a href="index.php" style="display:flex;align-items:center;gap:.75rem;text-decoration:none;color:#fff;">
                <span style="display:flex;width:2.5rem;height:2.5rem;align-items:center;justify-content:center;border-radius:.75rem;background:#FF5E00;font-weight:900;font-size:.85rem;color:#fff;">ID</span>
                <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1rem;font-weight:900;letter-spacing:-.01em;text-transform:uppercase;">IDTC</span>
            </a>
            <div style="display:flex;align-items:center;gap:.75rem;">
                <a href="artikel.php" class="back-link">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Semua Artikel
                </a>
            </div>
        </div>
    </header>

    <?php if ($article): ?>
    <main class="container-custom" style="max-width:56rem;padding-top:2.5rem;padding-bottom:5rem;">

        <!-- Category & Date -->
        <div class="article-meta">
            <span class="article-meta-badge badge-category"><?php echo htmlspecialchars($article['category'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="article-meta-badge badge-date">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <?php echo htmlspecialchars(fmtDate($article['published_at']), ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <span class="article-meta-badge badge-author">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <?php echo htmlspecialchars($article['author'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>

        <!-- Title -->
        <h1 class="article-detail-title"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></h1>

        <!-- Hero Image -->
        <?php if (!empty($article['image_url'])): ?>
        <div class="article-hero">
            <img src="<?php echo htmlspecialchars($article['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>" loading="eager">
            <div class="article-hero-overlay"></div>
        </div>
        <?php endif; ?>

        <!-- Summary Quote -->
        <?php if (!empty($article['summary'])): ?>
        <blockquote class="article-summary-quote">
            <?php echo htmlspecialchars($article['summary'], ENT_QUOTES, 'UTF-8'); ?>
        </blockquote>
        <?php endif; ?>

        <!-- Article Body -->
        <article class="article-body">
            <?php echo nl2br(htmlspecialchars($article['content'], ENT_QUOTES, 'UTF-8')); ?>
        </article>

        <!-- Share / Back -->
        <hr class="section-divider">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <a href="artikel.php" class="back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Kembali ke Semua Artikel
            </a>
            <a href="https://wa.me/?text=<?php echo urlencode($article['title'] . ' — ' . SITE_URL . '/artikel_detail.php?slug=' . $article['slug']); ?>" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:.4rem;font-size:.75rem;font-weight:700;color:#25D366;text-decoration:none;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.75.75 0 00.917.918l4.458-1.495A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.4 0-4.637-.8-6.426-2.148l-.448-.338-2.648.888.888-2.648-.338-.448A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                Bagikan
            </a>
        </div>

        <!-- Related Articles -->
        <?php if (!empty($relatedArticles)): ?>
        <hr class="section-divider">
        <div style="margin-bottom:1rem;">
            <span style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.18em;color:#FF5E00;">ARTIKEL LAINNYA</span>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin-top:.25rem;">Baca Juga</h2>
        </div>
        <div class="related-grid">
            <?php foreach ($relatedArticles as $rel): ?>
            <a href="artikel_detail.php?slug=<?php echo htmlspecialchars($rel['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="related-card">
                <?php if (!empty($rel['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($rel['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($rel['title'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                <?php else: ?>
                    <div style="width:100%;height:160px;background:#1a1a1a;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#555;">IDTC ARTICLE</div>
                <?php endif; ?>
                <div class="related-card-body">
                    <div>
                        <span style="font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#FF5E00;"><?php echo htmlspecialchars($rel['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <h3><?php echo htmlspecialchars($rel['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    </div>
                    <p><?php echo htmlspecialchars(fmtDate($rel['published_at']), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </main>

    <?php else: ?>
    <!-- 404 State -->
    <div class="article-not-found">
        <div style="width:5rem;height:5rem;border-radius:50%;background:rgba(255,94,0,.1);border:1px solid rgba(255,94,0,.2);display:flex;align-items:center;justify-content:center;">
            <svg width="32" height="32" fill="none" stroke="#FF5E00" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h1>Artikel Tidak Ditemukan</h1>
        <p>Artikel yang Anda cari tidak tersedia atau belum dipublikasikan.</p>
        <a href="artikel.php" class="btn-primary" style="margin-top:1rem;padding:.85rem 2rem;font-size:.85rem;">
            Lihat Semua Artikel
        </a>
    </div>
    <?php endif; ?>

</body>
</html>
