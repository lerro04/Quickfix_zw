(function () {
    var cfg = window.QFX_NOTIFY || {};
    if (!cfg.endpoint || !cfg.baseUrl) return;

    var POLL_MS = 6 * 1000;  // poll every 6 seconds
    var STORAGE_SINCE = 'qfx_notify_since';
    var STORAGE_SEEN = 'qfx_notify_seen';
    var STORAGE_PERM = 'qfx_notify_perm_asked';

    var seen = {};
    try { seen = JSON.parse(localStorage.getItem(STORAGE_SEEN) || '{}') || {}; } catch (e) { seen = {}; }
    // Trim seen-set to avoid unbounded growth.
    var seenKeys = Object.keys(seen);
    if (seenKeys.length > 500) {
        seenKeys.slice(0, seenKeys.length - 400).forEach(function (k) { delete seen[k]; });
    }

    function injectPermissionPrompt() {
        if (!('Notification' in window)) return;
        if (Notification.permission !== 'default') return;
        if (localStorage.getItem(STORAGE_PERM) === '1') return;

        var bar = document.createElement('div');
        bar.style.cssText = 'position:fixed;right:16px;bottom:16px;background:#1a1a2e;color:#fff;padding:12px 14px;border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,0.18);z-index:9999;font-size:0.92rem;max-width:320px;display:flex;gap:10px;align-items:center';
        bar.innerHTML = '<span>Get instant alerts for new bids, bookings &amp; messages?</span>';
        var enable = document.createElement('button');
        enable.textContent = 'Enable';
        enable.style.cssText = 'background:#e65c00;border:0;color:#fff;padding:6px 12px;border-radius:6px;cursor:pointer;font-weight:600';
        var dismiss = document.createElement('button');
        dismiss.textContent = 'No';
        dismiss.style.cssText = 'background:transparent;border:0;color:#bbb;cursor:pointer;font-size:0.85rem';
        enable.onclick = function () {
            Notification.requestPermission().then(function () {
                localStorage.setItem(STORAGE_PERM, '1');
                bar.remove();
            });
        };
        dismiss.onclick = function () {
            localStorage.setItem(STORAGE_PERM, '1');
            bar.remove();
        };
        bar.appendChild(enable);
        bar.appendChild(dismiss);
        document.body.appendChild(bar);
    }

    function showToast(item) {
        // In-page toast (always shown — works even without OS-level permission).
        var t = document.createElement('div');
        t.style.cssText = 'position:fixed;right:16px;top:80px;background:#fff;color:#1a1a2e;padding:12px 14px;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,0.18);z-index:9998;max-width:320px;border-left:4px solid #e65c00;cursor:pointer;animation:qfxSlideIn 0.25s ease-out';
        t.innerHTML = '<div style="font-weight:700;margin-bottom:2px;font-size:0.92rem">' + escapeHtml(item.title) + '</div>' +
            '<div style="font-size:0.85rem;color:#555;line-height:1.4">' + escapeHtml(item.body || '') + '</div>';
        t.addEventListener('click', function () { window.location.href = cfg.baseUrl + item.url; });
        document.body.appendChild(t);
        setTimeout(function () { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 500); }, 7000);

        // OS notification if permitted.
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                var n = new Notification(item.title, {
                    body: item.body || '',
                    tag: item.id,
                    icon: cfg.baseUrl + '/favicon.ico'
                });
                n.onclick = function () { window.focus(); window.location.href = cfg.baseUrl + item.url; n.close(); };
            } catch (e) { /* some browsers throw on data: URLs etc. — ignore */ }
        }
    }

    function escapeHtml(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

    function updateBadge(unread) {
        // Update any element with class .nav-badge inside a link to messages.php.
        var links = document.querySelectorAll('.navbar-nav a[href*="messages.php"]');
        links.forEach(function (link) {
            var existing = link.querySelector('.nav-badge');
            if (unread > 0) {
                if (existing) { existing.textContent = unread; }
                else {
                    var b = document.createElement('span');
                    b.className = 'nav-badge';
                    b.textContent = unread;
                    link.appendChild(document.createTextNode(' '));
                    link.appendChild(b);
                }
            } else if (existing) {
                existing.remove();
            }
        });
    }

    function poll() {
        if (document.hidden) return;  // skip when tab is in background — saves cycles & DB load
        var since = localStorage.getItem(STORAGE_SINCE) || '';
        var url = cfg.endpoint + (since ? '?since=' + encodeURIComponent(since) : '');
        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !data.ok) return;
                if (typeof data.unread_messages === 'number') updateBadge(data.unread_messages);
                (data.items || []).forEach(function (item) {
                    if (seen[item.id]) return;
                    seen[item.id] = 1;
                    showToast(item);
                });
                try { localStorage.setItem(STORAGE_SEEN, JSON.stringify(seen)); } catch (e) { }
                if (data.now) localStorage.setItem(STORAGE_SINCE, data.now);
            })
            .catch(function () { /* ignore — try again next tick */ });
    }

    // Inject keyframes
    var style = document.createElement('style');
    style.textContent = '@keyframes qfxSlideIn{from{transform:translateX(40px);opacity:0}to{transform:translateX(0);opacity:1}}';
    document.head.appendChild(style);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectPermissionPrompt);
    } else {
        injectPermissionPrompt();
    }

    poll();
    setInterval(poll, POLL_MS);
    // Re-poll immediately when tab regains focus.
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
})();
