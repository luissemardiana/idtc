<?php
require_once __DIR__ . '/config.php';

$articles = [];
$pdo = getDbConnection();
if ($pdo) {
    autoPublishScheduledArticles($pdo);
    $stmt = $pdo->query("SELECT title, slug, category, author, summary, content, image_url, published_at FROM articles WHERE status = 'published' ORDER BY published_at DESC, created_at DESC");
    $articles = $stmt->fetchAll();
}

function articleDate($value) {
    if (!$value) return '';
    $date = new DateTime($value, new DateTimeZone('Asia/Jakarta'));
    return $date->format('j M Y') . ' | ' . $date->format('H:i') . ' WIB';
}

function articleExcerpt($article) {
    $text = trim($article['summary'] ?: $article['content']);
    return mb_strlen($text) > 180 ? mb_substr($text, 0, 177) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel | IDTC Drone</title>
    <meta name="description" content="Kumpulan artikel dan informasi terbaru dari Indonesia Drone Training Center.">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .article-archive-page { min-height: 100vh; background: #080808; }
        .article-archive-header { border-bottom: 1px solid rgba(255,255,255,.1); padding: 1.25rem 0; }
        .article-archive-list { display: grid; gap: 1rem; margin: 0 auto; max-width: 64rem; }
        .article-archive-card { overflow: hidden; border: 1px solid rgba(255,255,255,.1); border-radius: .75rem; background: #151515; transition: border-color .25s ease, transform .25s ease; }
        .article-archive-card:hover, .article-archive-card[open] { border-color: #ff5e00; }
        .article-archive-summary { display: flex; cursor: pointer; list-style: none; align-items: center; gap: 1rem; padding: .75rem; }
        .article-archive-summary::-webkit-details-marker { display: none; }
        .article-archive-summary::after { margin-left: auto; color: #ff5e00; content: '+'; font-size: 1.5rem; font-weight: 400; }
        .article-archive-card[open] .article-archive-summary::after { content: '-'; }
        .article-archive-media { display: flex; min-width: 0; gap: 1rem; padding: .75rem; }
        .article-archive-cover { width: 11rem; height: 7rem; flex: 0 0 11rem; border-radius: .5rem; object-fit: cover; }
        .article-archive-placeholder { display: grid; aspect-ratio: 16 / 9; place-items: center; background: #222; color: #888; font-size: .75rem; font-weight: 800; }
        .article-archive-placeholder { width: 11rem; height: 7rem; flex: 0 0 11rem; }
        .article-archive-copy { display: flex; min-width: 0; flex: 1; flex-direction: column; justify-content: center; }
        .article-archive-copy h2 { margin: 0; }
        .article-archive-detail { border-top: 1px solid rgba(255,255,255,.08); padding: 1rem 1.25rem 1.25rem; }
        @media (max-width: 639px) {
            .article-archive-media { gap: .75rem; }
            .article-archive-cover, .article-archive-placeholder { width: 6.5rem; height: 6.5rem; flex-basis: 6.5rem; }
            .article-archive-copy h2 { font-size: .95rem; }
        }
    </style>
</head>
<body class="article-archive-page text-slate-100 antialiased">
    <header class="article-archive-header">
        <div class="container-custom flex items-center justify-between gap-4">
            <a href="index.php#news" class="flex items-center gap-3 text-white">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-orange font-black">ID</span>
                <span class="font-heading text-lg font-black uppercase tracking-tight">IDTC Articles</span>
            </a>
            <a href="index.php#news" class="btn-secondary px-4 py-2 text-xs font-bold">Kembali</a>
        </div>
    </header>

    <main class="container-custom py-16 md:py-24">
        <div class="mb-12 max-w-2xl">
            <div class="badge-eyebrow">INFORMASI & JURNAL KRITIS</div>
            <h1 class="text-4xl font-black uppercase tracking-tight text-white sm:text-6xl">SEMUA ARTIKEL</h1>
            <p class="mt-4 text-base leading-relaxed text-slate-300">Kumpulan berita, wawasan, dan informasi terbaru dari Indonesia Drone Training Center.</p>
        </div>

        <?php if ($articles): ?>
            <div class="article-archive-list">
                <?php foreach ($articles as $article): ?>
                    <details class="article-archive-card">
                        <summary class="article-archive-summary" onclick="event.preventDefault(); this.parentElement.open = !this.parentElement.open;">
                            <?php if (!empty($article['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($article['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>" class="article-archive-cover" loading="lazy">
                            <?php else: ?>
                                <div class="article-archive-placeholder">IDTC ARTICLE</div>
                            <?php endif; ?>
                            <div class="article-archive-copy">
                            <div class="mb-2 flex items-center justify-between gap-3 text-[10px] font-bold uppercase tracking-wider">
                                <span class="text-primary-orange"><?php echo htmlspecialchars($article['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="text-slate-500"><?php echo htmlspecialchars(articleDate($article['published_at']), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <h2 class="text-xl font-black leading-snug text-white"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                        </summary>
                        <div class="article-archive-detail">
                            <p class="mb-5 text-sm leading-relaxed text-slate-300"><?php echo htmlspecialchars(articleExcerpt($article), ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="whitespace-pre-line text-sm leading-relaxed text-slate-200"><?php echo htmlspecialchars($article['content'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="rounded-xl border border-white/10 bg-dark-surface px-6 py-16 text-center">
                <h2 class="text-xl font-bold text-white">Belum Ada Artikel</h2>
                <p class="mt-2 text-sm text-slate-400">Artikel yang sudah dipublikasikan akan tampil di halaman ini.</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
