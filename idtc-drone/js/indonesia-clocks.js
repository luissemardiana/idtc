/**
 * IDTC — Jam Indonesia (WIB / WITA / WIT).
 *
 * A compact live clock that shows the *one* Indonesian time zone matching the
 * visitor's location: WIB (UTC+7), WITA (UTC+8), or WIT (UTC+9). Only hours &
 * minutes are displayed (no seconds). The clock is anchored to the server's
 * wall clock so it is correct the moment the page opens — however long the
 * page was closed before — and ticks while the page is open.
 *
 * Any element with id="idtc-indonesia-clocks" or [data-idtc-clock] becomes a
 * clock chip (desktop navbar, mobile drawer, admin header).
 */
(function () {
    'use strict';

    var SERVER_OFFSET_MS = null; // server epoch ms - local epoch ms (set after sync)
    var API_ROOT = '';           // absolute site root so api/time.php works from any folder
    var ZONES = [
        { key: 'WIB',  tz: 'Asia/Jakarta',  utc: 'UTC+7' },
        { key: 'WITA', tz: 'Asia/Makassar', utc: 'UTC+8' },
        { key: 'WIT',  tz: 'Asia/Jayapura', utc: 'UTC+9' }
    ];

    // Pick the Indonesian zone matching the visitor's device offset
    // (Asia/Jakarta +7 / Makassar +8 / Jayapura +9). Anything outside falls
    // back to WIB — the server & site default.
    function detectZone() {
        var offsetHours = 7;
        try { offsetHours = -(new Date().getTimezoneOffset()) / 60; } catch (e) { /* keep 7 */ }
        if (offsetHours === 8) return ZONES[1];
        if (offsetHours === 9) return ZONES[2];
        return ZONES[0];
    }
    var ACTIVE_ZONE = detectZone();

    // Self-contained look & feel (injected once) so the dark public site and
    // the light Control Center get consistent styled chips without duplicating
    // CSS in each page's stylesheet.
    function injectStyles() {
        if (document.getElementById('idtc-clocks-css')) return;
        var css = document.createElement('style');
        css.id = 'idtc-clocks-css';
        css.textContent = [
            '/* Default (dark) chip — user navbar / mobile drawer */',
            '[data-idtc-clock], #idtc-indonesia-clocks { line-height: 1; }',
            '#idtc-indonesia-clocks .idtc-clock-cell, [data-idtc-clock] .idtc-clock-cell {',
            '  display: inline-flex; align-items: center; gap: 9px;',
            '  padding: 6px 11px; border-radius: 12px;',
            '  background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.14);',
            '  backdrop-filter: blur(6px); transition: border-color 0.25s ease; white-space: nowrap;',
            '}',
            '#idtc-indonesia-clocks .idtc-clock-cell:hover, [data-idtc-clock] .idtc-clock-cell:hover { border-color: rgba(255, 94, 0, 0.55); }',
            '#idtc-indonesia-clocks .idtc-clock-zone, [data-idtc-clock] .idtc-clock-zone {',
            '  padding: 4px 7px; border-radius: 8px; background: rgba(255, 94, 0, 0.16);',
            '  color: #FF5E00; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; white-space: nowrap;',
            '}',
            '#idtc-indonesia-clocks .idtc-clock-main, [data-idtc-clock] .idtc-clock-main {',
            '  display: inline-flex; flex-direction: column; gap: 2px; align-items: flex-start;',
            '}',
            '#idtc-indonesia-clocks .idtc-clock-time, [data-idtc-clock] .idtc-clock-time {',
            '  font-family: "JetBrains Mono", "Cascadia Mono", ui-monospace, Menlo, Consolas, monospace;',
            '  font-size: 17px; font-weight: 700; color: #ffffff; line-height: 1;',
            '  font-variant-numeric: tabular-nums; letter-spacing: 0.01em;',
            '}',
            '#idtc-indonesia-clocks .idtc-clock-date, [data-idtc-clock] .idtc-clock-date {',
            '  font-size: 8.5px; font-weight: 600; color: #b9b3ab; text-transform: uppercase; letter-spacing: 0.07em; white-space: nowrap;',
            '}',
            '#idtc-indonesia-clocks .idtc-clock-utc, [data-idtc-clock] .idtc-clock-utc { display: none; }',
            '/* Mini variant (mobile drawer) — hide the date line to stay compact. */',
            '[data-idtc-clock].idtc-clock-mini .idtc-clock-date, .idtc-clock-mini .idtc-clock-date { display: none; }',
            '[data-idtc-clock].idtc-clock-mini .idtc-clock-cell, .idtc-clock-mini .idtc-clock-cell { padding: 4px 9px; gap: 7px; border-radius: 10px; }',
            '[data-idtc-clock].idtc-clock-mini .idtc-clock-time, .idtc-clock-mini .idtc-clock-time { font-size: 14px; }',
            '[data-idtc-clock].idtc-clock-mini .idtc-clock-zone, .idtc-clock-mini .idtc-clock-zone { padding: 3px 6px; font-size: 9px; }',
            '/* Navbar chip (beside ID|EN) — one compact row, date hidden. */',
            '#idtc-indonesia-clocks.idtc-clock-nav .idtc-clock-date, .idtc-clock-nav .idtc-clock-date { display: none; }',
            '#idtc-indonesia-clocks.idtc-clock-nav .idtc-clock-cell, .idtc-clock-nav .idtc-clock-cell { padding: 3px 6px; gap: 7px; border: 0; background: transparent; backdrop-filter: none; }',
            '#idtc-indonesia-clocks.idtc-clock-nav .idtc-clock-time, .idtc-clock-nav .idtc-clock-time { font-size: 14px; }',
            '#idtc-indonesia-clocks.idtc-clock-nav .idtc-clock-zone, .idtc-clock-nav .idtc-clock-zone { padding: 2px 6px; font-size: 9px; }',
            '#idtc-indonesia-clocks.idtc-clock-nav .idtc-clock-cell:hover, .idtc-clock-nav .idtc-clock-cell:hover { border-color: transparent; }',
            '/* Light theme override (Control Center header). */',
            '.light-clock-wrap #idtc-indonesia-clocks .idtc-clock-cell {',
            '  background: transparent; border: 0; padding: 3px 6px; gap: 8px;',
            '}',
            '.light-clock-wrap #idtc-indonesia-clocks .idtc-clock-zone { background: #FFF0E5; color: #FF5E00; }',
            '.light-clock-wrap #idtc-indonesia-clocks .idtc-clock-time { font-size: 15px; color: #111111; }',
            '.light-clock-wrap #idtc-indonesia-clocks .idtc-clock-date { color: #6a5b3a; font-size: 8px; }'
        ].join('\n');
        document.head.appendChild(css);
    }

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    // The authoritative instant = "right now on the IDTC server" (site runs on WIB).
    function nowMs() {
        return Date.now() + (SERVER_OFFSET_MS || 0);
    }

    // Format the active zone: Indonesian date + HH:MM (seconds intentionally omitted).
    function formatParts(tz, ms) {
        var parts = {};
        new Intl.DateTimeFormat('id-ID', {
            timeZone: tz,
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            hour: 'numeric', minute: 'numeric',
            hourCycle: 'h23'
        }).formatToParts(new Date(ms)).forEach(function (p) {
            if (p.type !== 'literal') parts[p.type] = p.value;
        });
        return {
            date: parts.weekday + ', ' + parts.day + ' ' + parts.month + ' ' + parts.year,
            time: pad(parseInt(parts.hour, 10)) + ':' + pad(parseInt(parts.minute, 10))
        };
    }

    function cellHtml(z, ms) {
        var p = formatParts(z.tz, ms);
        return '<span class="idtc-clock-cell" data-zone="' + z.key + '" title="Jam ' + z.key + ' (' + z.utc + ')">' +
            '<span class="idtc-clock-zone">' + z.key + '</span>' +
            '<span class="idtc-clock-utc">' + z.utc + '</span>' +
            '<span class="idtc-clock-main">' +
                '<span class="idtc-clock-time">' + p.time + '</span>' +
                '<span class="idtc-clock-date">' + p.date + '</span>' +
            '</span>' +
            '</span>';
    }

    function roots() {
        var all = [];
        var byId = document.getElementById('idtc-indonesia-clocks');
        if (byId) all.push(byId);
        Array.prototype.forEach.call(document.querySelectorAll('[data-idtc-clock]'), function (el) {
            if (all.indexOf(el) === -1) all.push(el);
        });
        return all;
    }

    function render() {
        var list = roots();
        if (!list.length) return;
        var html = cellHtml(ACTIVE_ZONE, nowMs());
        list.forEach(function (root) { if (!root.dataset.rendered) { root.innerHTML = html; root.dataset.rendered = '1'; } });
    }

    function tick() {
        var list = roots();
        if (!list.length) return;
        var ms = nowMs();
        var p = formatParts(ACTIVE_ZONE.tz, ms);
        list.forEach(function (root) {
            var cell = root.querySelector('.idtc-clock-cell');
            if (!cell) return;
            var timeEl = cell.querySelector('.idtc-clock-time');
            var dateEl = cell.querySelector('.idtc-clock-date');
            if (timeEl && timeEl.textContent !== p.time) timeEl.textContent = p.time;
            if (dateEl && dateEl.textContent !== p.date) dateEl.textContent = p.date;
        });
    }

    function startTicking() {
        injectStyles();
        render();
        window.setInterval(tick, 1000);
    }

    // Sync with the server clock once, then start ticking. The anchor makes the
    // clock correct even when the visitor's device clock drifts.
    async function init() {
        try {
            var script = document.currentScript ||
                document.querySelector('script[src*="indonesia-clocks.js"]');
            if (script && script.src) {
                API_ROOT = script.src.replace(/js\/indonesia-clocks\.js[^/]*$/, '');
            }
            var res = await fetch(API_ROOT + 'api/time.php?t=' + Date.now(), { cache: 'no-store' });
            var data = await res.json();
            if (data && data.status === 'success' && data.server_ms) {
                SERVER_OFFSET_MS = data.server_ms - Date.now();
            }
        } catch (e) { /* offline: fall back to device clock (still labeled correctly) */ }
        startTicking();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
