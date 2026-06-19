/**
 * SmartAqua Dashboard — Main Module
 * Handles: clock, theme toggle, data polling, notifications, history
 */
const SmartAquaDashboard = (function () {
    'use strict';

    let pollingInterval = null;
    let notifInterval = null;
    const POLL_MS = 1500;
    const NOTIF_MS = 15000;

    /* ---------------------------------------------------------------
       INIT
    --------------------------------------------------------------- */
    function init() {
        if (!window.SmartAqua || !window.SmartAqua.currentPond) return;
        initClock();
        initThemeToggle();
        fetchLatestData();
        fetchNotifications();
        fetchHistory(1);
        startPolling();
        document.addEventListener('visibilitychange', handleVisibility);
    }

    /* ---------------------------------------------------------------
       REAL-TIME CLOCK
    --------------------------------------------------------------- */
    function initClock() {
        function update() {
            var now = new Date();
            var dateOpts = { day: '2-digit', month: 'long', year: 'numeric' };
            var dateStr = now.toLocaleDateString('id-ID', dateOpts);
            var timeStr = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }) + ' WIB';
            var el = document.getElementById('navDateTime');
            if (el) el.textContent = dateStr + '  ' + timeStr;
        }
        update();
        setInterval(update, 1000);
    }

    /* ---------------------------------------------------------------
       THEME TOGGLE (dark ↔ light)
    --------------------------------------------------------------- */
    function initThemeToggle() {
        var toggle = document.getElementById('themeToggle');
        var icon = document.getElementById('themeIcon');
        var html = document.documentElement;

        // Load saved preference (default dark)
        var saved = localStorage.getItem('sa-theme') || 'dark';
        html.setAttribute('data-theme', saved);
        updateThemeIcon(saved, icon);

        if (toggle) {
            toggle.addEventListener('click', function () {
                var current = html.getAttribute('data-theme');
                var next = current === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                localStorage.setItem('sa-theme', next);
                updateThemeIcon(next, icon);
                // Notify chart module so it can re-colour axes / grid
                if (window.SmartAquaCharts && window.SmartAquaCharts.updateTheme) {
                    window.SmartAquaCharts.updateTheme();
                }
            });
        }
    }

    function updateThemeIcon(theme, icon) {
        if (!icon) return;
        // In dark mode show sun (switch to light), in light show moon (switch to dark)
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }

    /* ---------------------------------------------------------------
       URL BUILDER
    --------------------------------------------------------------- */
    function buildUrl(template, pondCode) {
        return template.replace(':pondCode', pondCode);
    }

    /* ---------------------------------------------------------------
       FETCH LATEST SENSOR DATA
    --------------------------------------------------------------- */
    function fetchLatestData() {
        var pond = window.SmartAqua.currentPond;
        if (!pond) return;

        fetch(buildUrl(window.SmartAqua.urls.latestData, pond), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) updateCards(data.data);
        })
        .catch(function (err) { console.warn('Fetch error:', err); });
    }

    /* ---------------------------------------------------------------
       UPDATE ALL DASHBOARD CARDS
    --------------------------------------------------------------- */
    function updateCards(data) {
        var r = data.reading;
        var p = data.pump;
        var cfg = window.SmartAqua.pondConfig;

        // Debug log to help diagnose display issues
        console.log('[Dashboard] latestData:', {
            water_level: r ? r.water_level : null,
            ph_value: r ? r.ph_value : null,
            flow_rate: r ? r.flow_rate : null,
            reading: r,
            pump: p
        });

        if (r) {
            // Water level
            animateValue('water-level-value', r.water_level);
            updateBadge('water-level-status', r.water_level, cfg.minWaterLevel, cfg.maxWaterLevel, 'level');

            // pH
            animateValue('ph-value', r.ph_value);
            updateBadge('ph-status', r.ph_value, cfg.minPh, cfg.maxPh, 'ph');
        }

        if (p) {
            // Pump auto status
            var autoStatusEl = document.getElementById('pump-auto-status');
            if (autoStatusEl) {
                var isAutoOn = p.is_on && !p.is_manual_mode;
                autoStatusEl.textContent = isAutoOn ? 'HIDUP' : 'MATI';
                autoStatusEl.className = 'sa-pump-status-value ' + (isAutoOn ? 'on' : 'off');
            }

            // Pump auto toggle + label
            var autoToggle = document.getElementById('pumpAutoToggle');
            if (autoToggle) {
                autoToggle.checked = !p.is_manual_mode;
            }
            var autoLabel = document.getElementById('pumpAutoLabel');
            if (autoLabel) {
                autoLabel.textContent = !p.is_manual_mode ? 'AKTIF' : 'NONAKTIF';
            }

            // Pump manual status
            var manualStatusEl = document.getElementById('pump-manual-status');
            if (manualStatusEl) {
                var isManualOn = p.is_on && p.is_manual_mode;
                manualStatusEl.textContent = isManualOn ? 'HIDUP' : 'MATI';
                manualStatusEl.className = 'sa-pump-status-value ' + (isManualOn ? 'on' : 'off');
            }

            // Manual toggle state
            var manualToggle = document.getElementById('pumpManualToggle');
            if (manualToggle) manualToggle.checked = p.is_manual_mode;

            // Manual toggle label
            var manualLabel = document.getElementById('pumpManualLabel');
            if (manualLabel) {
                manualLabel.textContent = p.is_manual_mode ? 'AKTIF' : 'NONAKTIF';
            }

            // Show/hide manual ON/OFF buttons
            var manualBtns = document.getElementById('manualButtons');
            if (manualBtns) manualBtns.style.display = p.is_manual_mode ? 'flex' : 'none';
        }
    }

    /* ---------------------------------------------------------------
       ANIMATE A NUMERIC VALUE CHANGE
    --------------------------------------------------------------- */
    function animateValue(id, value) {
        var el = document.getElementById(id);
        if (!el) {
            console.warn('[Dashboard] Element not found:', id);
            return;
        }
        var formatted = (typeof value === 'number')
            ? (Number.isInteger(value) ? String(value) : value.toFixed(1))
            : String(value);

        console.log('[Dashboard] Updating', id, '->', formatted, '(raw:', value, ')');

        if (el.textContent !== formatted) {
            el.textContent = formatted;
            el.classList.add('sa-pulse');
            setTimeout(function () { el.classList.remove('sa-pulse'); }, 600);
        }
    }

    /* ---------------------------------------------------------------
       UPDATE STATUS BADGE
    --------------------------------------------------------------- */
    function updateBadge(id, value, min, max, type) {
        var el = document.getElementById(id);
        if (!el) return;

        var status, cls;
        if (value < min) {
            status = 'RENDAH';
            cls = 'danger';
        } else if (value > max) {
            status = 'TINGGI';
            cls = 'danger';
        } else {
            status = 'NORMAL';
            cls = 'normal';
        }
        el.textContent = status;
        el.className = 'sa-badge-status ' + cls;
    }

    /* ---------------------------------------------------------------
       NOTIFICATIONS
    --------------------------------------------------------------- */
    function fetchNotifications() {
        var pond = window.SmartAqua.currentPond;
        if (!pond) return;

        fetch(buildUrl(window.SmartAqua.urls.notifications, pond), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) renderNotifications(data.data);
        })
        .catch(function (err) { console.warn('Notif error:', err); });
    }

    function renderNotifications(notifications) {
        var panel = document.getElementById('notificationList');
        if (!panel) return;

        if (!notifications || !notifications.length) {
            panel.innerHTML =
                '<li class="sa-notification-item" style="justify-content:center;color:var(--sa-text-muted);">' +
                'Tidak ada notifikasi</li>';
            return;
        }

        // Update bell badge
        var unread = notifications.filter(function (n) { return !n.is_read; }).length;
        var badge = document.getElementById('unreadCount');
        if (badge) {
            badge.textContent = unread;
            badge.style.display = unread > 0 ? 'flex' : 'none';
        }

        panel.innerHTML = notifications.map(function (n) {
            var iconCls = n.type === 'danger' ? 'danger'
                : n.type === 'warning' ? 'warning'
                : n.type === 'success' ? 'success'
                : 'info';

            var iconMap = {
                danger:  '<i class="bi bi-exclamation-triangle-fill"></i>',
                warning: '<i class="bi bi-exclamation-circle-fill"></i>',
                success: '<i class="bi bi-check-circle-fill"></i>',
                info:    '<i class="bi bi-info-circle-fill"></i>'
            };
            var iconSymbol = iconMap[iconCls] || iconMap.info;

            var badgeType = (n.type === 'danger' || n.type === 'warning') ? 'peringatan' : 'info';
            var badgeText = badgeType === 'peringatan' ? 'Peringatan' : 'Info';

            var time = n.created_at
                ? new Date(n.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                : '';

            return '<li class="sa-notification-item">' +
                '<span class="sa-notification-icon ' + iconCls + '">' + iconSymbol + '</span>' +
                '<span class="sa-notification-time">' + time + '</span>' +
                '<span class="sa-notification-text">' + (n.message || n.title) + '</span>' +
                '<span class="sa-notification-badge ' + badgeType + '">' + badgeText + '</span>' +
                '</li>';
        }).join('');
    }

    /* ---------------------------------------------------------------
       HISTORY TABLE
    --------------------------------------------------------------- */
    function fetchHistory(page) {
        var pond = window.SmartAqua.currentPond;
        if (!pond) return;

        fetch(buildUrl(window.SmartAqua.urls.history, pond) + '?page=' + page, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) renderHistory(data.data);
        })
        .catch(function (err) { console.warn('History error:', err); });
    }

    function renderHistory(paginated) {
        var tbody = document.getElementById('historyBody');
        if (!tbody) return;

        var items = paginated.data || paginated;
        if (!items || !items.length) {
            tbody.innerHTML =
                '<tr><td colspan="4" style="text-align:center;color:var(--sa-text-muted);">Belum ada riwayat</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(function (h) {
            var time = h.created_at
                ? new Date(h.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                : '-';
            var meta = h.metadata || {};
            var level = meta.water_level ? meta.water_level + ' cm' : '-';
            var ph = meta.ph_value || '-';
            var status = h.event_type === 'alert'
                ? '<span class="badge-warning">Warning</span>'
                : '<span class="badge-normal">Normal</span>';
            return '<tr>' +
                '<td>' + time + '</td>' +
                '<td>' + level + '</td>' +
                '<td>' + ph + '</td>' +
                '<td>' + status + '</td>' +
                '</tr>';
        }).join('');
    }

    /* ---------------------------------------------------------------
       POLLING
    --------------------------------------------------------------- */
    function startPolling() {
        pollingInterval = setInterval(fetchLatestData, POLL_MS);
        notifInterval = setInterval(fetchNotifications, NOTIF_MS);
    }

    function stopPolling() {
        clearInterval(pollingInterval);
        clearInterval(notifInterval);
    }

    function handleVisibility() {
        if (document.hidden) {
            stopPolling();
        } else {
            fetchLatestData();
            fetchNotifications();
            startPolling();
        }
    }

    /* ---------------------------------------------------------------
       BOOTSTRAP
    --------------------------------------------------------------- */
    document.addEventListener('DOMContentLoaded', init);

    // Public API
    return {
        fetchLatestData: fetchLatestData,
        fetchNotifications: fetchNotifications,
        fetchHistory: fetchHistory
    };
})();
