import { Head, Link, useForm, usePage } from '@inertiajs/react';

const statusLabels = {
    pending: 'Pendente',
    in_progress: 'Em execucao',
    done: 'Concluido',
    confirmed: 'Confirmada',
    checked_in: 'Entrada efectuada',
    checked_out: 'Saída efectuada',
    planned: 'Planeada',
};

function StatusBadge({ value }) {
    const label = statusLabels[value] || value || 'Aberto';
    const color =
        value === 'done' || value === 'confirmed' || value === 'checked_in'
            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'
            : value === 'in_progress'
              ? 'border-amber-500/30 bg-amber-500/10 text-amber-200'
              : 'border-zinc-600 bg-zinc-800 text-zinc-300';

    return (
        <span className={`rounded-full border px-2 py-1 text-[11px] ${color}`}>
            {label}
        </span>
    );
}

function Section({ title, action, children }) {
    return (
        <section className="space-y-3">
            <div className="flex items-center justify-between">
                <h2 className="text-base font-semibold text-white">{title}</h2>
                {action}
            </div>
            {children}
        </section>
    );
}

function EmptyState({ children }) {
    return (
        <div className="rounded-lg border border-zinc-800 bg-zinc-900/70 px-4 py-5 text-sm text-zinc-400">
            {children}
        </div>
    );
}

function Shortcut({ href, label, code }) {
    return (
        <a
            href={href}
            className="flex min-h-20 flex-col justify-between rounded-lg border border-zinc-800 bg-zinc-900 p-3 text-left active:border-amber-400"
        >
            <span className="flex h-8 w-8 items-center justify-center rounded-md bg-amber-400 text-xs font-black text-zinc-950">
                {code}
            </span>
            <span className="text-sm font-medium text-zinc-100">{label}</span>
        </a>
    );
}

function ProofForm({ item }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        photo: null,
        latitude: '',
        longitude: '',
        qr_code: '',
    });

    const submit = (event) => {
        event.preventDefault();

        post(`/mobile/checklists/${item.id}/complete`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset('photo', 'qr_code'),
        });
    };

    const captureLocation = () => {
        if (!navigator.geolocation) {
            return;
        }

        navigator.geolocation.getCurrentPosition((position) => {
            setData({
                ...data,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            });
        });
    };

    return (
        <form onSubmit={submit} className="mt-4 space-y-3">
            <div>
                <label className="text-xs text-zinc-400">Fotografia de prova</label>
                <input
                    type="file"
                    accept="image/*"
                    capture="environment"
                    required
                    onChange={(event) =>
                        setData('photo', event.target.files?.[0] || null)
                    }
                    className="mt-1 block w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-xs text-zinc-200 file:mr-3 file:rounded file:border-0 file:bg-amber-400 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-zinc-950"
                />
                {errors.photo && (
                    <p className="mt-1 text-xs text-red-300">{errors.photo}</p>
                )}
            </div>

            <div className="grid grid-cols-[1fr_auto] gap-2">
                <input
                    value={data.qr_code}
                    onChange={(event) => setData('qr_code', event.target.value)}
                    required={item.requires_qr}
                    placeholder={item.requires_qr ? 'QR do quarto' : 'Código QR ou referência'}
                    className="rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-xs text-zinc-200"
                />
                <button
                    type="button"
                    onClick={captureLocation}
                    className="rounded-md border border-zinc-700 px-3 py-2 text-xs font-semibold text-zinc-200"
                >
                    GPS
                </button>
            </div>
            {item.requires_qr && (
                <p className="text-xs text-amber-200">
                    QR do quarto obrigatório para validar esta limpeza.
                </p>
            )}
            {errors.qr_code && (
                <p className="text-xs text-red-300">{errors.qr_code}</p>
            )}

            {(data.latitude || data.longitude) && (
                <p className="text-xs text-emerald-200">
                    Localizacao captada: {Number(data.latitude).toFixed(5)},{' '}
                    {Number(data.longitude).toFixed(5)}
                </p>
            )}

            <button
                type="submit"
                disabled={processing}
                className="w-full rounded-md bg-amber-400 px-3 py-2 text-xs font-bold text-zinc-950 disabled:opacity-60"
            >
                {processing ? 'A guardar...' : 'Concluir com prova'}
            </button>
        </form>
    );
}

export default function MobileApp({
    summary,
    reservations,
    tasks,
    checklists,
    lowStock,
}) {
    const user = usePage().props.auth.user;

    return (
        <>
            <Head title="App mobile">
                <meta name="theme-color" content="#09090b" />
            </Head>

            <main className="min-h-screen bg-zinc-950 text-zinc-100">
                <div className="mx-auto flex min-h-screen w-full max-w-md flex-col px-4 pb-24 pt-4">
                    <header className="sticky top-0 z-10 -mx-4 border-b border-zinc-900 bg-zinc-950/95 px-4 pb-4 pt-2 backdrop-blur">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs uppercase tracking-[0.18em] text-amber-300">
                                    AYA LodgeOS
                                </p>
                                <h1 className="mt-1 text-2xl font-bold text-white">
                                    Operacao diaria
                                </h1>
                            </div>
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold">
                                {user?.name
                                    ?.split(' ')
                                    .map((part) => part[0])
                                    .join('')
                                    .slice(0, 2)}
                            </div>
                        </div>
                        <p className="mt-2 text-sm text-zinc-400">
                            {summary.date}
                        </p>
                    </header>

                    <div className="mt-5 grid grid-cols-2 gap-3">
                        <div className="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                            <p className="text-xs text-zinc-400">Ocupacao</p>
                            <p className="mt-2 text-3xl font-bold text-white">
                                {summary.occupancy}%
                            </p>
                            <p className="text-xs text-zinc-500">
                                {summary.rooms} quartos
                            </p>
                        </div>
                        <div className="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                            <p className="text-xs text-zinc-400">
                                Receita hoje
                            </p>
                            <p className="mt-2 text-xl font-bold text-white">
                                {summary.revenue_today}
                            </p>
                            <p className="text-xs text-zinc-500">
                                pagamentos recebidos
                            </p>
                        </div>
                        <div className="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                            <p className="text-xs text-zinc-400">
                                Tarefas abertas
                            </p>
                            <p className="mt-2 text-3xl font-bold text-white">
                                {summary.pending_tasks}
                            </p>
                            <p className="text-xs text-zinc-500">
                                operacional
                            </p>
                        </div>
                        <div className="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                            <p className="text-xs text-zinc-400">Stock baixo</p>
                            <p className="mt-2 text-3xl font-bold text-white">
                                {summary.low_stock}
                            </p>
                            <p className="text-xs text-zinc-500">
                                artigos a repor
                            </p>
                        </div>
                    </div>

                    <section className="mt-6">
                        <div className="grid grid-cols-3 gap-3">
                            <Shortcut
                                href="/admin/reservations"
                                label="Reservas"
                                code="R"
                            />
                            <Shortcut
                                href="/admin/payments"
                                label="Pagamentos"
                                code="P"
                            />
                            <Shortcut
                                href="/admin/operational-tasks"
                                label="Tarefas"
                                code="T"
                            />
                            <Shortcut
                                href="/admin/daily-checklists"
                                label="Checklist"
                                code="C"
                            />
                            <Shortcut
                                href="/admin/stock-items"
                                label="Stock"
                                code="S"
                            />
                            <Shortcut
                                href="/admin"
                                label="Painel"
                                code="A"
                            />
                        </div>
                    </section>

                    <div className="mt-7 space-y-7">
                        <Section
                            title="Reservas de hoje"
                            action={
                                <a
                                    href="/admin/reservations"
                                    className="text-xs font-medium text-amber-300"
                                >
                                    Ver todas
                                </a>
                            }
                        >
                            {reservations.length === 0 ? (
                                <EmptyState>
                                    Sem reservas ativas para hoje.
                                </EmptyState>
                            ) : (
                                <div className="space-y-3">
                                    {reservations.map((reservation) => (
                                        <article
                                            key={reservation.code}
                                            className="rounded-lg border border-zinc-800 bg-zinc-900 p-4"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-semibold text-white">
                                                        {reservation.guest}
                                                    </p>
                                                    <p className="mt-1 text-xs text-zinc-400">
                                                        {reservation.room} -{' '}
                                                        {reservation.code}
                                                    </p>
                                                </div>
                                                <StatusBadge
                                                    value={reservation.status}
                                                />
                                            </div>
                                            <div className="mt-3 flex items-center justify-between text-xs text-zinc-400">
                                                <span>
                                                    {reservation.check_in} -{' '}
                                                    {reservation.check_out}
                                                </span>
                                                <span className="font-semibold text-zinc-100">
                                                    {reservation.total}
                                                </span>
                                            </div>
                                        </article>
                                    ))}
                                </div>
                            )}
                        </Section>

                        <Section title="Tarefas operacionais">
                            {tasks.length === 0 ? (
                                <EmptyState>Sem tarefas pendentes.</EmptyState>
                            ) : (
                                <div className="space-y-3">
                                    {tasks.map((task) => (
                                        <article
                                            key={`${task.title}-${task.date}`}
                                            className="rounded-lg border border-zinc-800 bg-zinc-900 p-4"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-semibold text-white">
                                                        {task.title}
                                                    </p>
                                                    <p className="mt-1 text-xs text-zinc-400">
                                                        {task.room ||
                                                            'Sem quarto'}{' '}
                                                        -{' '}
                                                        {task.staff ||
                                                            'Sem responsavel'}
                                                    </p>
                                                </div>
                                                <StatusBadge
                                                    value={task.status}
                                                />
                                            </div>
                                            <p className="mt-3 text-xs text-zinc-500">
                                                {task.date || 'Sem data'} -{' '}
                                                prioridade {task.priority}
                                            </p>
                                        </article>
                                    ))}
                                </div>
                            )}
                        </Section>

                        <Section title="Checklist de hoje">
                            {checklists.length === 0 ? (
                                <EmptyState>
                                    Sem checklist registada para hoje.
                                </EmptyState>
                            ) : (
                                <div className="space-y-3">
                                    {checklists.map((item) => (
                                        <article
                                            key={`${item.id}-${item.area}-${item.title}`}
                                            className="rounded-lg border border-zinc-800 bg-zinc-900 p-4"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-semibold text-white">
                                                        {item.title}
                                                    </p>
                                                    <p className="mt-1 text-xs text-zinc-400">
                                                        {item.room || item.area} -{' '}
                                                        {item.staff ||
                                                            'Sem responsavel'}
                                                    </p>
                                                </div>
                                                <StatusBadge value={item.status} />
                                            </div>
                                            {item.status === 'done' ? (
                                                <p className="mt-3 text-xs text-emerald-200">
                                                    {item.has_evidence
                                                        ? 'Prova registada.'
                                                        : 'Concluido.'}
                                                </p>
                                            ) : (
                                                <ProofForm item={item} />
                                            )}
                                        </article>
                                    ))}
                                </div>
                            )}
                        </Section>

                        <Section title="Stock a repor">
                            {lowStock.length === 0 ? (
                                <EmptyState>
                                    Nenhum artigo abaixo do minimo.
                                </EmptyState>
                            ) : (
                                <div className="space-y-3">
                                    {lowStock.map((item) => (
                                        <article
                                            key={item.name}
                                            className="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900 p-4"
                                        >
                                            <div>
                                                <p className="text-sm font-semibold text-white">
                                                    {item.name}
                                                </p>
                                                <p className="mt-1 text-xs text-zinc-400">
                                                    Minimo {item.minimum}{' '}
                                                    {item.unit}
                                                </p>
                                            </div>
                                            <p className="text-sm font-bold text-amber-200">
                                                {item.quantity} {item.unit}
                                            </p>
                                        </article>
                                    ))}
                                </div>
                            )}
                        </Section>
                    </div>
                </div>

                <nav className="fixed inset-x-0 bottom-0 border-t border-zinc-800 bg-zinc-950/95 px-4 py-3 backdrop-blur">
                    <div className="mx-auto grid max-w-md grid-cols-4 gap-2 text-center text-xs">
                        <Link href="/mobile" className="text-amber-300">
                            Hoje
                        </Link>
                        <a href="/admin/reservations" className="text-zinc-400">
                            Reservas
                        </a>
                        <a href="/admin/payments" className="text-zinc-400">
                            Caixa
                        </a>
                        <a href="/admin" className="text-zinc-400">
                            Admin
                        </a>
                    </div>
                </nav>
            </main>
        </>
    );
}
