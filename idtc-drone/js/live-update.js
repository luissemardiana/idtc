/**
 * IDTC — Live content sync for visitors.
 *
 * Quietly watches for admin content changes (api/revision.php) and reloads the
 * page automatically so the public site always shows the latest content without
 * the visitor having to refresh. Scroll position is restored after reload, and
 * the poller pauses while the built-in Content Studio overlay is open so admins
 * are never interrupted mid-edit.
 */
(function () {
    'use strict';

    var POLL_MS = 6000;
    var knownRevision = null;
    var adminPortal = document.getElementById('admin-portal-view');

    // Restore the scroll position after an automatic reload.
    try {
        var savedScroll = sessionStorage.getItem('idtc_scroll_restore');
        if (savedScroll) {
            sessionStorage.removeItem('idtc_scroll_restore');
            window.scrollTo(0, parseInt(savedScroll, 10) || 0);
        }
    } catch (e) { /* sessionStorage unavailable — ignore */ }

    function isEditingInStudio() {
        if (!adminPortal) return false;
        return !adminPortal.classList.contains('hidden');
    }

    async function check() {
        if (document.hidden || isEditingInStudio()) return;
        try {
            var response = await fetch('api/revision.php?v=' + Date.now(), { cache: 'no-store' });
            var data = await response.json();
            if (!data || data.status !== 'success' || !data.revision) return;
            if (knownRevision === null) {
                knownRevision = data.revision;
                return;
            }
            if (data.revision !== knownRevision) {
                knownRevision = data.revision;
                try { sessionStorage.setItem('idtc_scroll_restore', String(window.scrollY || 0)); } catch (e) {}
                window.location.reload();
            }
        } catch (e) {
            // Offline or temporary error — retry on the next tick.
        }
    }

    window.setInterval(function () {
        if (!document.hidden) check();
    }, POLL_MS);

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) check();
    });
})();
