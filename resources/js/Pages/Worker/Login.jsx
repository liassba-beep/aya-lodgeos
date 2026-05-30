import { Head, useForm } from '@inertiajs/react';

export default function WorkerLogin() {
    const { data, setData, post, processing, errors } = useForm({
        phone: '',
        pin: '',
    });

    const submit = (event) => {
        event.preventDefault();
        post('/trabalhador/login');
    };

    return (
        <main className="flex min-h-screen items-center justify-center bg-zinc-950 px-4 text-zinc-100">
            <Head title="Entrada do trabalhador" />

            <form
                onSubmit={submit}
                className="w-full max-w-sm rounded-xl border border-zinc-800 bg-zinc-900 p-5 shadow-2xl"
            >
                <p className="text-xs uppercase tracking-[0.2em] text-amber-300">
                    AYA LodgeOS
                </p>
                <h1 className="mt-2 text-2xl font-bold">Perfil mobile</h1>
                <p className="mt-1 text-sm text-zinc-400">
                    Entrada para equipas de operação.
                </p>

                <div className="mt-6 space-y-4">
                    <div>
                        <label className="text-sm font-medium">Telemóvel</label>
                        <input
                            value={data.phone}
                            onChange={(event) =>
                                setData('phone', event.target.value)
                            }
                            inputMode="tel"
                            className="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-3 text-base"
                            autoFocus
                        />
                        {errors.phone && (
                            <p className="mt-1 text-xs text-red-300">
                                {errors.phone}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">PIN</label>
                        <input
                            value={data.pin}
                            onChange={(event) =>
                                setData('pin', event.target.value)
                            }
                            type="password"
                            inputMode="numeric"
                            className="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-3 text-base"
                        />
                        {errors.pin && (
                            <p className="mt-1 text-xs text-red-300">
                                {errors.pin}
                            </p>
                        )}
                    </div>
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="mt-6 w-full rounded-lg bg-amber-400 px-4 py-3 text-sm font-bold text-zinc-950 disabled:opacity-60"
                >
                    {processing ? 'A entrar...' : 'Entrar'}
                </button>
            </form>
        </main>
    );
}
