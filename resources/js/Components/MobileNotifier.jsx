import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function playTone(severity = 'info') {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const context = new AudioContext();
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        const firstNote = severity === 'warning' ? 740 : 880;
        const secondNote = severity === 'warning' ? 520 : 660;

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(firstNote, context.currentTime);
        oscillator.frequency.setValueAtTime(secondNote, context.currentTime + 0.12);
        gain.gain.setValueAtTime(0.001, context.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.12, context.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.28);

        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start();
        oscillator.stop(context.currentTime + 0.3);
        window.setTimeout(() => context.close(), 600);
    } catch {
        // Sound is best-effort; some browsers block it before the first tap.
    }
}

export default function MobileNotifier({
    endpoint,
    storageKey,
    intervalMs = 60000,
    reloadOnOpen = true,
}) {
    const [notice, setNotice] = useState(null);
    const [isOnline, setIsOnline] = useState(
        typeof navigator === 'undefined' ? true : navigator.onLine,
    );
    const soundReady = useRef(false);
    const firstRun = useRef(true);

    useEffect(() => {
        const enableSound = () => {
            soundReady.current = true;
        };

        window.addEventListener('pointerdown', enableSound, { once: true });
        window.addEventListener('keydown', enableSound, { once: true });

        return () => {
            window.removeEventListener('pointerdown', enableSound);
            window.removeEventListener('keydown', enableSound);
        };
    }, []);

    useEffect(() => {
        const updateOnlineState = () => setIsOnline(navigator.onLine);

        window.addEventListener('online', updateOnlineState);
        window.addEventListener('offline', updateOnlineState);

        return () => {
            window.removeEventListener('online', updateOnlineState);
            window.removeEventListener('offline', updateOnlineState);
        };
    }, []);

    useEffect(() => {
        let cancelled = false;

        const poll = async () => {
            if (document.hidden || !navigator.onLine) {
                return;
            }

            try {
                const response = await fetch(endpoint, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const revision = data.revision || data.latest?.id || '';

                if (!revision || cancelled) {
                    return;
                }

                const lastSeen = window.localStorage.getItem(storageKey);
                window.localStorage.setItem(storageKey, revision);

                if (firstRun.current) {
                    firstRun.current = false;

                    if (data.overdue_count > 0 && data.latest) {
                        setNotice(data.latest);
                    }

                    return;
                }

                if (lastSeen && lastSeen !== revision && data.latest) {
                    setNotice(data.latest);

                    if (soundReady.current) {
                        playTone(data.latest.severity);
                    }
                }
            } catch {
                setIsOnline(false);
            }
        };

        poll();
        const interval = window.setInterval(poll, intervalMs);
        const onVisible = () => {
            if (!document.hidden) {
                poll();
            }
        };

        document.addEventListener('visibilitychange', onVisible);

        return () => {
            cancelled = true;
            window.clearInterval(interval);
            document.removeEventListener('visibilitychange', onVisible);
        };
    }, [endpoint, intervalMs, storageKey]);

    return (
        <>
            {!isOnline && (
                <div className="sticky top-0 z-30 rounded-lg border border-amber-400/40 bg-amber-400 px-3 py-2 text-center text-xs font-bold text-zinc-950 shadow-lg">
                    Sem internet. Pode ver dados guardados. Envie fotos quando voltar a rede.
                </div>
            )}

            {notice && (
                <div className="sticky top-0 z-40 rounded-xl border border-amber-300/50 bg-zinc-900 p-3 shadow-2xl">
                    <p className="text-xs font-bold uppercase tracking-wide text-amber-300">
                        Aviso
                    </p>
                    <p className="mt-1 text-sm font-bold text-white">
                        {notice.title || 'Nova informação'}
                    </p>
                    <p className="mt-1 text-xs leading-5 text-zinc-300">
                        {notice.message || 'Abra a lista para ver detalhes.'}
                    </p>
                    <div className="mt-3 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            onClick={() => setNotice(null)}
                            className="rounded-lg border border-zinc-700 px-3 py-2 text-xs font-bold"
                        >
                            OK
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                setNotice(null);
                                if (reloadOnOpen) {
                                    router.reload({ preserveScroll: true });
                                }
                            }}
                            className="rounded-lg bg-amber-400 px-3 py-2 text-xs font-bold text-zinc-950"
                        >
                            Actualizar
                        </button>
                    </div>
                </div>
            )}
        </>
    );
}
