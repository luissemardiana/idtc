document.addEventListener('DOMContentLoaded', () => {
    const escapeHtml = value => String(value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    try {
        if (window.IDTC_DB_CMS) {
            Object.entries(window.IDTC_DB_CMS).forEach(([key, value]) => {
                if (key === 'hero-headline') {
                    const lines = String(value).trim().split(/\s+/).filter(Boolean).slice(0, 4);
                    document.querySelectorAll('[data-hero-headline]').forEach(element => {
                        if (lines.length) element.innerHTML = lines.map(line => `<span class="hero-headline-line">${escapeHtml(line)}</span>`).join('');
                    });
                    return;
                }
                document.querySelectorAll(`[data-i18n="${key}"]`).forEach(element => {
                    element.textContent = value;
                });
            });

            const heroBackground = window.IDTC_DB_CMS['hero-background'];
            if (heroBackground) document.querySelectorAll('[data-hero-background]').forEach(element => { element.style.backgroundImage = `url("${heroBackground.replace(/"/g, '\\"')}")`; });
            const primaryLink = window.IDTC_DB_CMS['hero-cta-primary-link'];
            if (primaryLink) document.querySelectorAll('[data-hero-link="primary"]').forEach(element => { element.href = primaryLink; });
            const secondaryLink = window.IDTC_DB_CMS['hero-cta-secondary-link'];
            if (secondaryLink) document.querySelectorAll('[data-hero-link="secondary"]').forEach(element => { element.href = secondaryLink; });
        }
    } catch (error) { console.warn('CMS content unavailable.', error); }
});

document.addEventListener('DOMContentLoaded', async () => {
    const escapeHtml = value => String(value || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    try {
        const galleryResponse = await fetch('api/modules.php?module=gallery&action=list');
        const gallery = await galleryResponse.json();
        
        const galleryWrap = document.getElementById('public-gallery-folders');
        if (galleryWrap && gallery.data && gallery.data.length) {
            const categories = [...new Map(gallery.data.map(item => [item.category, item])).values()];
            galleryWrap.innerHTML = categories.map(item => { const category = String(item.category).toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-|-$/g, ''); return `<a href="gallery.php?category=${encodeURIComponent(category)}" class="gallery-folder group"><div class="folder-tab"></div><div class="folder-body"><div class="folder-preview"><img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.title)}" loading="lazy"><div class="folder-preview-overlay"></div></div><div class="folder-info"><h4 class="folder-name">${escapeHtml(item.category)}</h4><p class="folder-count">Open gallery</p></div></div></a>`; }).join('');
        }
    } catch (error) { console.warn('Public gallery content unavailable.', error); }
});
