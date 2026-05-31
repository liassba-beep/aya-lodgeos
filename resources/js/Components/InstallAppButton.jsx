import { useEffect, useState } from 'react';

function isStandalone() {
    return (
        window.matchMedia?.('(display-mode: standalone)').matches ||
        window.navigator.standalone === true
    );
}

export default function InstallAppButton() {
    const [promptEvent, setPromptEvent] = useState(null);
    const [installed, setInstalled] = useState(false);

    useEffect(() => {
        setInstalled(isStandalone());

        const onBeforeInstallPrompt = (event) => {
            event.preventDefault();
            setPromptEvent(event);
        };

        const onInstalled = () => {
            setInstalled(true);
            setPromptEvent(null);
        };

        window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt);
        window.addEventListener('appinstalled', onInstalled);

        return () => {
            window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt);
            window.removeEventListener('appinstalled', onInstalled);
        };
    }, []);

    if (installed) {
        return (
            <div className="rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-3 py-2 text-xs font-semibold text-emerald-200">
                App instalada neste telemóvel.
            </div>
        );
    }

    if (!promptEvent) {
        return null;
    }

    return (
        <button
            type="button"
            onClick={async () => {
                await promptEvent.prompt();
                setPromptEvent(null);
            }}
            className="w-full rounded-lg bg-emerald-400 px-3 py-2 text-sm font-bold text-zinc-950"
        >
            Instalar app no Android
        </button>
    );
}
