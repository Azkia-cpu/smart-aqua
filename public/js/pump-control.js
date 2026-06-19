/**
 * SmartAqua — Pump Control Module
 * Handles manual/auto pump mode toggle and manual ON/OFF commands
 */
const SmartAquaPump = (function () {
    'use strict';

    /* ---------------------------------------------------------------
       INIT
    --------------------------------------------------------------- */
    function init() {
        if (!window.SmartAqua || !window.SmartAqua.currentPond) return;

        var autoToggle = document.getElementById('pumpAutoToggle');
        var manualToggle = document.getElementById('pumpManualToggle');

        // Automatic mode toggle
        if (autoToggle) {
            autoToggle.addEventListener('change', function () {
                var action = this.checked ? 'toggle_manual_off' : 'toggle_manual_on';
                var msg = this.checked
                    ? 'Aktifkan mode pompa otomatis?'
                    : 'Matikan mode pompa otomatis dan beralih ke mode manual?';

                if (confirm(msg)) {
                    sendPumpCommand(action);
                } else {
                    this.checked = !this.checked;
                }
            });
        }

        // Manual mode toggle
        if (manualToggle) {
            manualToggle.addEventListener('change', function () {
                var action = this.checked ? 'toggle_manual_on' : 'toggle_manual_off';
                var msg = this.checked
                    ? 'Aktifkan mode pompa manual?'
                    : 'Kembali ke mode otomatis?';

                if (confirm(msg)) {
                    sendPumpCommand(action);
                } else {
                    this.checked = !this.checked;
                }
            });
        }

        // Manual ON button
        var onBtn = document.getElementById('pumpOnBtn');
        if (onBtn) {
            onBtn.addEventListener('click', function () {
                sendPumpCommand('on');
            });
        }

        // Manual OFF button
        var offBtn = document.getElementById('pumpOffBtn');
        if (offBtn) {
            offBtn.addEventListener('click', function () {
                sendPumpCommand('off');
            });
        }
    }

    /* ---------------------------------------------------------------
       SEND PUMP COMMAND
    --------------------------------------------------------------- */
    function sendPumpCommand(action) {
        var pond = window.SmartAqua.currentPond;

        fetch(window.SmartAqua.urls.pumpControl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.SmartAqua.csrfToken
            },
            body: JSON.stringify({
                pond_code: pond,
                action: action
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success && window.SmartAquaDashboard) {
                window.SmartAquaDashboard.fetchLatestData();
            }
            if (!data.success && data.message) {
                alert(data.message);
            }
        })
        .catch(function (err) {
            console.error('Pump command error:', err);
            alert('Gagal mengirim perintah pompa. Silakan coba lagi.');
        });
    }

    /* ---------------------------------------------------------------
       BOOTSTRAP
    --------------------------------------------------------------- */
    document.addEventListener('DOMContentLoaded', init);

    // Public API
    return {
        sendPumpCommand: sendPumpCommand
    };
})();
