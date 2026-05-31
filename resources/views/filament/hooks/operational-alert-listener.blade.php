<script>
    (() => {
        if (!window.location.pathname.startsWith('/admin')) {
            return;
        }

        const storageKey = 'aya-lodgeos:last-operational-alert-id';
        const overdueStorageKey = 'aya-lodgeos:last-overdue-alert-signature';
        let audioContext = null;

        const playAlertSound = () => {
            try {
                audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();

                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(660, audioContext.currentTime + 0.12);
                gain.gain.setValueAtTime(0.001, audioContext.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.16, audioContext.currentTime + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.28);

                oscillator.connect(gain);
                gain.connect(audioContext.destination);
                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.3);
            } catch (error) {
                // Browsers may block sound until the first user interaction.
            }
        };

        const showPopup = (alert, heading = 'Novo alerta operacional') => {
            const previous = document.querySelector('[data-aya-alert-popup]');

            if (previous) {
                previous.remove();
            }

            const popup = document.createElement('a');
            popup.dataset.ayaAlertPopup = 'true';
            popup.href = '/admin/operational-alerts';
            popup.style.cssText = [
                'position:fixed',
                'right:24px',
                'bottom:24px',
                'z-index:99999',
                'max-width:360px',
                'border:1px solid rgba(245,158,11,.55)',
                'border-radius:14px',
                'background:#111827',
                'box-shadow:0 20px 45px rgba(0,0,0,.35)',
                'color:white',
                'padding:16px',
                'font-family:Inter,ui-sans-serif,system-ui,sans-serif',
                'text-decoration:none',
            ].join(';');
            popup.innerHTML = `
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <div style="height:34px;width:34px;border-radius:999px;background:#f59e0b;color:#111827;display:grid;place-items:center;font-weight:800">!</div>
                    <div>
                        <div style="font-size:14px;font-weight:700;margin-bottom:4px">${heading}</div>
                        <div style="font-size:15px;font-weight:800;margin-bottom:4px">${alert.title || 'Alerta'}</div>
                        <div style="font-size:13px;line-height:1.45;color:rgba(255,255,255,.72)">${alert.message || 'Clique para abrir os alertas.'}</div>
                    </div>
                </div>
            `;

            document.body.appendChild(popup);
            window.setTimeout(() => popup.remove(), 12000);
        };

        const checkAlerts = async () => {
            try {
                const response = await fetch('/operational-alerts/latest', {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const latest = data.latest;
                const overdue = data.overdue;

                if (latest?.id) {
                    const lastSeen = Number(window.localStorage.getItem(storageKey) || 0);

                    if (Number(latest.id) > lastSeen) {
                        window.localStorage.setItem(storageKey, latest.id);
                        showPopup(latest);
                        playAlertSound();
                        return;
                    }
                }

                if (overdue?.count > 0 && overdue.signature) {
                    const lastOverdueSeen = window.localStorage.getItem(overdueStorageKey);

                    if (lastOverdueSeen !== overdue.signature) {
                        window.localStorage.setItem(overdueStorageKey, overdue.signature);
                        showPopup(overdue, 'Atividade atrasada');
                        playAlertSound();
                    }
                }
            } catch (error) {
                // Alert polling is best-effort and must not disturb the panel.
            }
        };

        window.setTimeout(checkAlerts, 1500);
        window.setInterval(checkAlerts, 20000);
    })();
</script>
