/* =============================================================================
   IDTC MODERN NOTIFICATION SYSTEM
   -----------------------------------------------------------------------------
   Self-contained: injects its own <style> + DOM, exposes three globals:

     notifyToast(message, type[, title])
       type: 'success' | 'error' | 'warning' | 'info'
       Modern glassy toast, top-right stack, auto-dismiss with progress bar.

     notifyConfirm({ title, message, confirmText, cancelText, danger, actionClass })
       -> Promise<boolean>. Modern centered dialog. Resolves true on confirm.

     notifyInline(el, message, type[, title])
       Styles an existing inline <p>/<span> message element as a modern notice
       chip (icon + tinted box). type: 'success' | 'error' | 'warning' | 'info'.
       Pass empty message to clear the element.

   Author: Codebuff (IDTC Drone)
   ========================================================================== */
(function () {
    'use strict';

    var NS = 'idtc-notify';
    var STYLE_ID = NS + '-styles';
    var TOAST_ROOT_ID = NS + '-toast-root';
    var MODAL_ID = NS + '-confirm';

    var TYPE_META = {
        success: { icon: 'check',   tint: '#10B981', ring: 'rgba(16,185,129,.38)' },
        error:   { icon: 'x',       tint: '#EF4444', ring: 'rgba(239,68,68,.38)' },
        warning: { icon: 'alert',   tint: '#F59E0B', ring: 'rgba(245,158,11,.4)' },
        info:    { icon: 'info',    tint: '#FF5E00', ring: 'rgba(255,94,0,.4)' }
    };

    function iconSvg(name, cls) {
        var common = 'class="' + (cls || '') + '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"';
        var paths = {
            check: '<path d="M20 6L9 17l-5-5"/>',
            x: '<path d="M18 6L6 18M6 6l12 12"/>',
            alert: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4m0 4h.01"/>',
            info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/>'
        };
        return '<svg ' + common + '>' + (paths[name] || paths.info) + '</svg>';
    }

    /* ---------------------------- CSS ---------------------------- */
    function ensureStyles() {
        if (document.getElementById(STYLE_ID)) return;
        var css = [
            '#' + TOAST_ROOT_ID + '{position:fixed;top:1rem;right:1rem;z-index:2147483000;display:flex;flex-direction:column;gap:.65rem;pointer-events:none;max-width:min(92vw,380px)}',
            '#' + TOAST_ROOT_ID + ' .nt-toast{pointer-events:auto;display:flex;align-items:flex-start;gap:.75rem;padding:.85rem .9rem;border-radius:1rem;background:rgba(20,20,22,.92);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.09);box-shadow:0 18px 46px -14px rgba(0,0,0,.55),0 2px 8px rgba(0,0,0,.2);color:#F5F5F4;overflow:hidden;position:relative;transform:translateX(calc(100% + 1.5rem));opacity:0;transition:transform .34s cubic-bezier(.2,.9,.25,1.1),opacity .28s ease;animation:none}',
            '#' + TOAST_ROOT_ID + ' .nt-toast.nt-in{transform:none;opacity:1}',
            '#' + TOAST_ROOT_ID + ' .nt-toast.nt-out{transform:translateX(calc(100% + 1.5rem));opacity:0;transition:transform .28s ease,opacity .25s ease}',
            '#' + TOAST_ROOT_ID + ' .nt-toast .nt-toast-ic{flex:none;width:2.05rem;height:2.05rem;border-radius:.65rem;display:grid;place-items:center;margin-top:.05rem}',
            '#' + TOAST_ROOT_ID + ' .nt-toast .nt-toast-ic svg{width:1.1rem;height:1.1rem}',
            '#' + TOAST_ROOT_ID + ' .nt-toast .nt-toast-body{flex:1;min-width:0}',
            '#' + TOAST_ROOT_ID + ' .nt-toast .nt-toast-title{display:block;font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:.15rem}',
            '#' + TOAST_ROOT_ID + ' .nt-toast .nt-toast-text{font-size:.83rem;line-height:1.5;color:#E7E5E4;word-break:break-word}',
            '#' + TOAST_ROOT_ID + ' .nt-toast .nt-toast-close{flex:none;width:1.5rem;height:1.5rem;border-radius:.45rem;display:grid;place-items:center;color:#A8A29E;background:transparent;border:0;cursor:pointer;transition:background .18s,color .18s}',
            '#' + TOAST_ROOT_ID + ' .nt-toast .nt-toast-close:hover{background:rgba(255,255,255,.12);color:#fff}',
            '#' + TOAST_ROOT_ID + ' .nt-toast .nt-toast-close svg{width:.95rem;height:.95rem}',
            '#' + TOAST_ROOT_ID + ' .nt-toast .nt-toast-bar{position:absolute;left:0;bottom:0;height:3px;border-radius:0 3px 3px 0;transform-origin:left;animation:ntBar linear forwards}',
            '@keyframes ntBar{from{transform:scaleX(1)}to{transform:scaleX(0)}}',
            '@media (prefers-reduced-motion: reduce){#' + TOAST_ROOT_ID + ' .nt-toast{transition:none;animation-duration:.01s !important}}',
            '@media (max-width:640px){#' + TOAST_ROOT_ID + '{left:1rem;right:1rem;max-width:none}}',

            /* Confirm dialog */
            '#' + MODAL_ID + '{position:fixed;inset:0;z-index:2147483001;display:none;align-items:center;justify-content:center;padding:1.25rem;background:rgba(8,8,10,.62);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}',
            '#' + MODAL_ID + '.nt-show{display:flex}',
            '#' + MODAL_ID + ' .nt-confirm-card{width:100%;max-width:430px;border-radius:1.4rem;background:#FDFDFB;border:1px solid rgba(216,201,160,.65);box-shadow:0 30px 80px -20px rgba(0,0,0,.45);overflow:hidden;transform:scale(.94) translateY(10px);opacity:0;transition:transform .3s cubic-bezier(.2,.9,.25,1.08),opacity .25s ease}',
            '#' + MODAL_ID + '.nt-show .nt-confirm-card{transform:none;opacity:1}',
            '#' + MODAL_ID + ' .nt-confirm-top{display:flex;gap:.9rem;align-items:flex-start;padding:1.4rem 1.4rem .4rem}',
            '#' + MODAL_ID + ' .nt-confirm-ic{flex:none;width:2.7rem;height:2.7rem;border-radius:.85rem;display:grid;place-items:center;margin-top:.1rem}',
            '#' + MODAL_ID + ' .nt-confirm-ic svg{width:1.45rem;height:1.45rem}',
            '#' + MODAL_ID + ' .nt-confirm-title{font-size:1rem;font-weight:900;letter-spacing:.02em;text-transform:uppercase;color:#111;line-height:1.3}',
            '#' + MODAL_ID + ' .nt-confirm-sub{font-size:.68rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#B78A2D;margin-top:.1rem}',
            '#' + MODAL_ID + ' .nt-confirm-msg{margin:1rem 1.4rem 0;font-size:.88rem;line-height:1.65;color:#44403C}',
            '#' + MODAL_ID + ' .nt-confirm-actions{display:flex;justify-content:flex-end;gap:.6rem;padding:1.2rem 1.4rem 1.35rem;margin-top:1.2rem;background:#F7F3E9;border-top:1px solid rgba(216,201,160,.45)}',
            '#' + MODAL_ID + ' .nt-confirm-actions button{border-radius:.75rem;padding:.6rem 1.1rem;font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;cursor:pointer;border:1px solid transparent;transition:background .18s,transform .18s,border-color .18s}',
            '#' + MODAL_ID + ' .nt-confirm-cancel{background:#fff;border-color:#D8C9A0;color:#44403C}',
            '#' + MODAL_ID + ' .nt-confirm-cancel:hover{background:#FBFAF7;border-color:#C9B68A}',
            '#' + MODAL_ID + ' .nt-confirm-ok{background:#111;color:#fff}',
            '#' + MODAL_ID + ' .nt-confirm-ok:hover{background:#FF5E00;transform:translateY(-1px)}',
            '#' + MODAL_ID + ' .nt-confirm-ok.nt-danger{background:#DC2626}',
            '#' + MODAL_ID + ' .nt-confirm-ok.nt-danger:hover{background:#B91C1C}',
            '#' + MODAL_ID + ' .nt-confirm-ok.nt-amber{background:#D97706}',
            '#' + MODAL_ID + ' .nt-confirm-ok.nt-amber:hover{background:#B45309}',
            '@media (prefers-reduced-motion: reduce){#' + MODAL_ID + ' .nt-confirm-card{transition:none}}',

            /* Inline notice chip */
            '.nt-inline{display:inline-flex;align-items:flex-start;gap:.5rem;border-radius:.8rem;padding:.55rem .8rem;font-size:.82rem;line-height:1.5;font-weight:600;border:1px solid transparent;max-width:100%}',
            '.nt-inline .nt-inline-ic{flex:none;width:1.15rem;height:1.15rem;margin-top:.08rem}',
            '.nt-inline .nt-inline-ic svg{width:100%;height:100%}',
            '.nt-inline.nt-success{background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.3);color:#047857}',
            '.nt-inline.nt-error{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.28);color:#B91C1C}',
            '.nt-inline.nt-warning{background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.32);color:#B45309}',
            '.nt-inline.nt-info{background:rgba(255,94,0,.07);border-color:rgba(255,94,0,.28);color:#C2410C}'
        ].join('\n');

        var style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = css;
        (document.head || document.documentElement).appendChild(style);
    }

    function ensureToastRoot() {
        var root = document.getElementById(TOAST_ROOT_ID);
        if (root) return root;
        root = document.createElement('div');
        root.id = TOAST_ROOT_ID;
        document.body.appendChild(root);
        return root;
    }

    /* ---------------------------- TOAST ---------------------------- */
    function notifyToast(message, type, title) {
        type = TYPE_META[type] ? type : 'info';
        var meta = TYPE_META[type];
        ensureStyles();
        var root = ensureToastRoot();

        var toast = document.createElement('div');
        toast.className = 'nt-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

        var iconWrap = document.createElement('span');
        iconWrap.className = 'nt-toast-ic';
        iconWrap.style.background = meta.ring;
        iconWrap.style.color = meta.tint;
        iconWrap.innerHTML = iconSvg(meta.icon);

        var body = document.createElement('div');
        body.className = 'nt-toast-body';
        if (title) {
            var titleEl = document.createElement('span');
            titleEl.className = 'nt-toast-title';
            titleEl.style.color = meta.tint;
            titleEl.textContent = title;
            body.appendChild(titleEl);
        }
        var textEl = document.createElement('span');
        textEl.className = 'nt-toast-text';
        textEl.textContent = message || '';
        body.appendChild(textEl);

        var close = document.createElement('button');
        close.className = 'nt-toast-close';
        close.setAttribute('aria-label', 'Tutup notifikasi');
        close.innerHTML = iconSvg('x');
        close.addEventListener('click', function () { dismiss(true); });

        var bar = document.createElement('span');
        bar.className = 'nt-toast-bar';
        bar.style.background = meta.tint;

        toast.appendChild(iconWrap);
        toast.appendChild(body);
        toast.appendChild(close);
        toast.appendChild(bar);
        root.appendChild(toast);

        var duration = type === 'error' ? 5200 : type === 'warning' ? 4500 : 3400;
        bar.style.animationDuration = duration + 'ms';

        // Force reflow then animate in
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { toast.classList.add('nt-in'); });
        });

        function dismiss(skipResolve) {
            if (toast._done) return;
            toast._done = true;
            toast.classList.remove('nt-in');
            toast.classList.add('nt-out');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }

        var timer = setTimeout(dismiss, duration);
        toast.addEventListener('mouseenter', function () { clearTimeout(timer); bar.style.animationPlayState = 'paused'; });
        toast.addEventListener('mouseleave', function () {
            if (toast._done) return;
            timer = setTimeout(dismiss, 1600);
            bar.style.animationPlayState = 'running';
        });
        // stack cap
        while (root.children.length > 5) root.removeChild(root.firstChild);
    }

    /* ---------------------------- CONFIRM ---------------------------- */
    function notifyConfirm(opts) {
        opts = opts || {};
        ensureStyles();

        var meta = opts.danger ? TYPE_META.error : (opts.amber ? TYPE_META.warning : TYPE_META.info);

        var old = document.getElementById(MODAL_ID);
        if (old && old.parentNode) old.parentNode.removeChild(old);

        var modal = document.createElement('div');
        modal.id = MODAL_ID;
        modal.innerHTML =
            '<div class="nt-confirm-card">' +
            '  <div class="nt-confirm-top">' +
            '    <span class="nt-confirm-ic" style="background:' + meta.ring + ';color:' + meta.tint + '">' + iconSvg(meta.icon) + '</span>' +
            '    <div><div class="nt-confirm-title"></div><div class="nt-confirm-sub"></div></div>' +
            '  </div>' +
            '  <div class="nt-confirm-msg"></div>' +
            '  <div class="nt-confirm-actions">' +
            '    <button type="button" class="nt-confirm-cancel"></button>' +
            '    <button type="button" class="nt-confirm-ok' + (opts.danger ? ' nt-danger' : (opts.amber ? ' nt-amber' : '')) + '"></button>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(modal);
        modal.querySelector('.nt-confirm-title').textContent = opts.title || 'Konfirmasi';
        modal.querySelector('.nt-confirm-sub').textContent = opts.subtitle || 'IDTC System';
        modal.querySelector('.nt-confirm-msg').textContent = opts.message || 'Lanjutkan tindakan ini?';
        modal.querySelector('.nt-confirm-cancel').textContent = opts.cancelText || 'Batal';
        modal.querySelector('.nt-confirm-ok').textContent = opts.confirmText || 'Ya, Lanjutkan';
        if (opts.actionClass) modal.querySelector('.nt-confirm-ok').className = 'nt-confirm-ok ' + opts.actionClass;

        var resolved = false;
        function resolveOnce(value) {
            if (resolved) return;
            resolved = true;
            modal.classList.remove('nt-show');
            setTimeout(function () { if (modal.parentNode) modal.parentNode.removeChild(modal); }, 240);
            if (typeof opts.onClose === 'function') opts.onClose(value);
        }

        modal.querySelector('.nt-confirm-cancel').addEventListener('click', function () { resolveOnce(false); });
        modal.querySelector('.nt-confirm-ok').addEventListener('click', function () {
            if (typeof opts.onConfirm === 'function') { opts.onConfirm(); resolveOnce(true); }
            else resolveOnce(true);
        });
        modal.addEventListener('click', function (e) { if (e.target === modal) resolveOnce(false); });

        var onKey = function (e) {
            if (e.key === 'Escape') { resolveOnce(false); document.removeEventListener('keydown', onKey); }
        };
        document.addEventListener('keydown', onKey);

        requestAnimationFrame(function () { modal.classList.add('nt-show'); });
        return new Promise(function (resolve) {
            var origResolve = resolve;
            // Expose resolution via callback for pre-promise callers
            var origOnClose = opts.onClose;
            opts.onClose = function (value) { if (origOnClose) origOnClose(value); origResolve(value); };
        });
    }

    /* ---------------------------- INLINE ---------------------------- */
    function notifyInline(el, message, type) {
        if (!el) return;
        type = TYPE_META[type] ? type : 'info';
        var meta = TYPE_META[type];

        if (!message) {
            el.className = '';
            el.textContent = '';
            el.innerHTML = '';
            el.style.display = 'none';
            return;
        }
        el.style.display = 'inline-flex';
        el.className = 'nt-inline nt-' + type;
        el.innerHTML = '<span class="nt-inline-ic" style="color:' + meta.tint + '">' + iconSvg(meta.icon) + '</span><span></span>';
        el.lastChild.textContent = message;
    }

    /* Form/context message: inline chip when the target element still exists,
       otherwise falls back to a transient toast (e.g. form re-rendered). */
    function notifyFormMessage(el, message, type) {
        if (!message) { if (el) notifyInline(el, ''); return; }
        if (el && el.isConnected) notifyInline(el, message, type);
        else notifyToast(message, type);
    }

    window.notifyToast = notifyToast;
    window.notifyConfirm = notifyConfirm;
    window.notifyInline = notifyInline;
    window.notifyFormMessage = notifyFormMessage;
    window.IDTCNotify = { toast: notifyToast, confirm: notifyConfirm, inline: notifyInline, formMessage: notifyFormMessage };
})();
