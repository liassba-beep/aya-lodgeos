import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InstallAppButton from '@/Components/InstallAppButton';
import MobileNotifier from '@/Components/MobileNotifier';

const statusLabels = {
    pending: 'Pendente',
    in_progress: 'Em execução',
    done: 'Concluído',
    confirmed: 'Confirmada',
    checked_in: 'Check-in',
    checked_out: 'Check-out',
};

function Badge({ value }) {
    return (
        <span className="rounded-full border border-amber-400/30 bg-amber-400/10 px-2 py-1 text-[11px] text-amber-200">
            {statusLabels[value] || value || 'Aberto'}
        </span>
    );
}

function CameraInput({
    onChange,
    required = false,
    label = 'Fotografia de prova',
    hint = 'No telemóvel abre a câmara; no computador permite escolher uma imagem.',
}) {
    return (
        <label className="block space-y-1">
            <span className="text-xs font-semibold text-zinc-200">
                {label}
            </span>
            <input
                type="file"
                accept="image/*"
                capture="environment"
                required={required}
                onChange={(event) => onChange(event.target.files?.[0] || null)}
                className="block w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-xs text-zinc-200 file:mr-3 file:rounded-md file:border-0 file:bg-amber-400 file:px-3 file:py-1 file:text-xs file:font-bold file:text-zinc-950"
            />
            {hint && <span className="block text-[11px] text-zinc-500">{hint}</span>}
        </label>
    );
}

function QrInput({ value, onChange, required = false, placeholder = 'Código QR' }) {
    const [message, setMessage] = useState('');

    const scanQr = async (event) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        if (!('BarcodeDetector' in window)) {
            setMessage('Este navegador não lê QR pela câmara. Escreva o código impresso no QR.');
            return;
        }

        try {
            const detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            const bitmap = await createImageBitmap(file);
            const codes = await detector.detect(bitmap);
            const rawValue = codes[0]?.rawValue || '';

            if (!rawValue) {
                setMessage('Não consegui ler o QR. Tente aproximar a câmara ou escreva o código.');
                return;
            }

            onChange(rawValue);
            setMessage('QR lido.');
        } catch {
            setMessage('Não consegui ler o QR. Escreva o código impresso no QR.');
        }
    };

    return (
        <div className="space-y-1">
            <div className="grid grid-cols-[1fr_auto] gap-2">
                <input
                    value={value}
                    required={required}
                    onChange={(event) => onChange(event.target.value)}
                    placeholder={placeholder}
                    className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-xs"
                />
                <label className="cursor-pointer rounded-lg border border-zinc-700 px-3 py-2 text-xs font-bold">
                    Ler QR
                    <input
                        type="file"
                        accept="image/*"
                        capture="environment"
                        onChange={scanQr}
                        className="sr-only"
                    />
                </label>
            </div>
            {message && <p className="text-[11px] text-zinc-500">{message}</p>}
        </div>
    );
}

function Section({ title, children }) {
    return (
        <section className="space-y-3">
            <h2 className="text-base font-semibold text-white">{title}</h2>
            {children}
        </section>
    );
}

function CompleteTaskForm({ task }) {
    const form = useForm({ photo: null, qr_code: '' });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(`/trabalhador/tarefas/${task.id}/concluir`, {
                    forceFormData: true,
                    preserveScroll: true,
                });
            }}
            className="mt-3 space-y-2"
        >
            <CameraInput
                label="Fotografia da tarefa"
                onChange={(file) => form.setData('photo', file)}
            />
            <QrInput
                value={form.data.qr_code}
                onChange={(value) => form.setData('qr_code', value)}
                placeholder="QR ou referência"
            />
            <button className="w-full rounded-lg bg-amber-400 px-3 py-2 text-xs font-bold text-zinc-950">
                Concluir tarefa
            </button>
        </form>
    );
}

function CompleteChecklistForm({ item }) {
    const form = useForm({
        photo: null,
        latitude: '',
        longitude: '',
        qr_code: '',
    });

    const getGps = () => {
        navigator.geolocation?.getCurrentPosition((position) => {
            form.setData({
                ...form.data,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            });
        });
    };

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(`/trabalhador/checklists/${item.id}/concluir`, {
                    forceFormData: true,
                    preserveScroll: true,
                });
            }}
            className="mt-3 space-y-2"
        >
            <CameraInput
                required
                label="Fotografia da limpeza concluída"
                onChange={(file) => form.setData('photo', file)}
            />
            {item.requires_qr && (
                <p className="text-xs text-amber-200">
                    QR do quarto obrigatório para validar a limpeza.
                </p>
            )}
            <div className="grid grid-cols-[1fr_auto] gap-2">
                <QrInput
                    value={form.data.qr_code}
                    required={item.requires_qr}
                    onChange={(value) => form.setData('qr_code', value)}
                    placeholder={item.requires_qr ? 'QR do quarto' : 'QR do ponto'}
                />
                <button
                    type="button"
                    onClick={getGps}
                    className="rounded-lg border border-zinc-700 px-3 py-2 text-xs font-bold"
                >
                    GPS
                </button>
            </div>
            {form.errors.qr_code && (
                <p className="text-xs text-red-300">{form.errors.qr_code}</p>
            )}
            {form.errors.photo && (
                <p className="text-xs text-red-300">{form.errors.photo}</p>
            )}
            <button className="w-full rounded-lg bg-amber-400 px-3 py-2 text-xs font-bold text-zinc-950">
                Concluir checklist
            </button>
        </form>
    );
}

function ReportForm({ rooms }) {
    const form = useForm({
        room_id: '',
        title: '',
        priority: 'normal',
        qr_code: '',
        notes: '',
        photo: null,
    });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post('/trabalhador/avarias', {
                    forceFormData: true,
                    preserveScroll: true,
                    onSuccess: () =>
                        form.reset('title', 'notes', 'qr_code', 'photo'),
                });
            }}
            className="rounded-xl border border-zinc-800 bg-zinc-900 p-4"
        >
            <div className="grid gap-2">
                <select
                    value={form.data.room_id}
                    onChange={(event) =>
                        form.setData('room_id', event.target.value)
                    }
                    className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                >
                    <option value="">Área geral</option>
                    {rooms.map((room) => (
                        <option key={room.id} value={room.id}>
                            {room.name}
                        </option>
                    ))}
                </select>
                <input
                    value={form.data.title}
                    onChange={(event) => form.setData('title', event.target.value)}
                    placeholder="Avaria ou incidente"
                    className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                />
                <select
                    value={form.data.priority}
                    onChange={(event) =>
                        form.setData('priority', event.target.value)
                    }
                    className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                >
                    <option value="normal">Normal</option>
                    <option value="high">Urgente</option>
                    <option value="critical">Crítico</option>
                </select>
                <QrInput
                    value={form.data.qr_code}
                    onChange={(value) => form.setData('qr_code', value)}
                    placeholder="QR do quarto ou referência"
                />
                <CameraInput
                    required
                    label="Fotografia da avaria"
                    onChange={(file) => form.setData('photo', file)}
                />
                <textarea
                    value={form.data.notes}
                    onChange={(event) => form.setData('notes', event.target.value)}
                    placeholder="Notas"
                    className="min-h-20 rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                />
                <button className="rounded-lg bg-amber-400 px-3 py-2 text-sm font-bold text-zinc-950">
                    Reportar avaria
                </button>
            </div>
        </form>
    );
}

function UtilityForm() {
    const form = useForm({
        meter_number: '',
        balance_kwh: '',
        balance_amount: '',
        qr_code: '',
        notes: '',
        photo: null,
    });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post('/trabalhador/credelec', {
                    forceFormData: true,
                    preserveScroll: true,
                    onSuccess: () => form.reset(),
                });
            }}
            className="rounded-xl border border-zinc-800 bg-zinc-900 p-4"
        >
            <div className="grid gap-2">
                <input
                    value={form.data.meter_number}
                    onChange={(event) =>
                        form.setData('meter_number', event.target.value)
                    }
                    placeholder="Número do contador"
                    className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                />
                <div className="grid grid-cols-2 gap-2">
                    <input
                        value={form.data.balance_kwh}
                        onChange={(event) =>
                            form.setData('balance_kwh', event.target.value)
                        }
                        inputMode="decimal"
                        placeholder="kWh"
                        className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                    />
                    <input
                        value={form.data.balance_amount}
                        onChange={(event) =>
                            form.setData('balance_amount', event.target.value)
                        }
                        inputMode="decimal"
                        placeholder="MZN"
                        className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                    />
                </div>
                <QrInput
                    value={form.data.qr_code}
                    onChange={(value) => form.setData('qr_code', value)}
                    placeholder="QR ou referência"
                />
                <CameraInput
                    required
                    label="Fotografia do contador"
                    onChange={(file) => form.setData('photo', file)}
                />
                <button className="rounded-lg bg-amber-400 px-3 py-2 text-sm font-bold text-zinc-950">
                    Guardar Credelec
                </button>
            </div>
        </form>
    );
}

function RequisitionForm({ stockItems }) {
    const form = useForm({ stock_item_id: '', quantity: '', notes: '' });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post('/trabalhador/requisicoes', {
                    preserveScroll: true,
                    onSuccess: () => form.reset('quantity', 'notes'),
                });
            }}
            className="rounded-xl border border-zinc-800 bg-zinc-900 p-4"
        >
            <div className="grid gap-2">
                <select
                    value={form.data.stock_item_id}
                    onChange={(event) =>
                        form.setData('stock_item_id', event.target.value)
                    }
                    className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                >
                    <option value="">Produto</option>
                    {stockItems.map((item) => (
                        <option key={item.id} value={item.id}>
                            {item.name} ({item.unit})
                        </option>
                    ))}
                </select>
                <input
                    value={form.data.quantity}
                    onChange={(event) =>
                        form.setData('quantity', event.target.value)
                    }
                    inputMode="decimal"
                    placeholder="Quantidade"
                    className="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                />
                <textarea
                    value={form.data.notes}
                    onChange={(event) => form.setData('notes', event.target.value)}
                    placeholder="Justificação"
                    className="min-h-16 rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm"
                />
                <button className="rounded-lg bg-amber-400 px-3 py-2 text-sm font-bold text-zinc-950">
                    Requisitar produto
                </button>
            </div>
        </form>
    );
}

function ReservationCard({ reservation }) {
    const form = useForm({ photo: null });

    return (
        <article className="rounded-xl border border-zinc-800 bg-zinc-900 p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold">{reservation.guest}</p>
                    <p className="mt-1 text-xs text-zinc-400">
                        {reservation.room} - {reservation.code}
                    </p>
                    <p className="mt-1 text-xs text-zinc-500">
                        {reservation.check_in} a {reservation.check_out}
                    </p>
                </div>
                <Badge value={reservation.status} />
            </div>
            {reservation.status !== 'checked_in' &&
                reservation.status !== 'checked_out' && (
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post(
                                `/trabalhador/reservas/${reservation.id}/check-in`,
                                {
                                    forceFormData: true,
                                    preserveScroll: true,
                                },
                            );
                        }}
                        className="mt-3 space-y-2"
                    >
                        <CameraInput
                            label="Fotografia do hóspede/documento"
                            onChange={(file) => form.setData('photo', file)}
                        />
                        <button className="w-full rounded-lg bg-emerald-400 px-3 py-2 text-xs font-bold text-zinc-950">
                            Check-in hóspede
                        </button>
                    </form>
                )}
            {reservation.status === 'checked_in' && (
                <button
                    onClick={() =>
                        router.post(
                            `/trabalhador/reservas/${reservation.id}/check-out`,
                            {},
                            { preserveScroll: true },
                        )
                    }
                    className="mt-3 w-full rounded-lg border border-zinc-700 px-3 py-2 text-xs font-bold"
                >
                    Check-out hóspede
                </button>
            )}
        </article>
    );
}

export default function WorkerApp({
    staff,
    tasks,
    checklists,
    reservations,
    stockItems,
    rooms,
}) {
    const checkInForm = useForm({ photo: null });

    return (
        <main className="min-h-screen bg-zinc-950 text-zinc-100">
            <Head title="Trabalhador mobile" />

            <div className="mx-auto flex min-h-screen w-full max-w-md flex-col gap-6 px-4 pb-24 pt-4">
                <header className="sticky top-0 z-10 -mx-4 border-b border-zinc-900 bg-zinc-950/95 px-4 pb-4 pt-2 backdrop-blur">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-xs uppercase tracking-[0.18em] text-amber-300">
                                {staff.property || 'AYA LodgeOS'}
                            </p>
                            <h1 className="mt-1 text-2xl font-bold">
                                {staff.name}
                            </h1>
                            <p className="text-sm text-zinc-400">
                                {staff.role}
                            </p>
                        </div>
                        <button
                            onClick={() => router.post('/trabalhador/logout')}
                            className="rounded-lg border border-zinc-700 px-3 py-2 text-xs"
                        >
                            Sair
                        </button>
                    </div>
                </header>

                <MobileNotifier
                    endpoint="/trabalhador/novidades"
                    storageKey="aya-lodgeos-worker-notifications"
                    intervalMs={60000}
                />

                <InstallAppButton />

                <section className="rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                    <p className="text-sm font-semibold">
                        Ponto diário com fotografia
                    </p>
                    {staff.checked_in ? (
                        <div className="mt-3">
                            <p className="text-xs text-emerald-200">
                                Check-in feito: {staff.checked_in_at}
                            </p>
                            <button
                                onClick={() =>
                                    router.post('/trabalhador/check-out')
                                }
                                className="mt-3 w-full rounded-lg border border-zinc-700 px-3 py-2 text-sm font-bold"
                            >
                                Fazer check-out
                            </button>
                        </div>
                    ) : (
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                checkInForm.post('/trabalhador/check-in', {
                                    forceFormData: true,
                                    preserveScroll: true,
                                });
                            }}
                            className="mt-3 space-y-3"
                        >
                            <CameraInput
                                required
                                label="Fotografia de entrada ao serviço"
                                onChange={(file) =>
                                    checkInForm.setData('photo', file)
                                }
                            />
                            <button className="w-full rounded-lg bg-amber-400 px-3 py-2 text-sm font-bold text-zinc-950">
                                Fazer entrada com fotografia
                            </button>
                        </form>
                    )}
                </section>

                <Section title="Tarefas">
                    {tasks.length === 0 ? (
                        <p className="rounded-xl border border-zinc-800 bg-zinc-900 p-4 text-sm text-zinc-400">
                            Sem tarefas abertas.
                        </p>
                    ) : (
                        tasks.map((task) => (
                            <article
                                key={task.id}
                                className="rounded-xl border border-zinc-800 bg-zinc-900 p-4"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold">
                                            {task.title}
                                        </p>
                                        <p className="mt-1 text-xs text-zinc-400">
                                            {task.room || 'Área geral'} -{' '}
                                            {task.date || 'sem data'}
                                        </p>
                                    </div>
                                    <Badge value={task.status} />
                                </div>
                                <CompleteTaskForm task={task} />
                            </article>
                        ))
                    )}
                </Section>

                <Section title="Checklist e limpezas">
                    {checklists.length === 0 ? (
                        <p className="rounded-xl border border-zinc-800 bg-zinc-900 p-4 text-sm text-zinc-400">
                            Sem limpezas para hoje.
                        </p>
                    ) : (
                        checklists.map((item) => (
                            <article
                                key={item.id}
                                className="rounded-xl border border-zinc-800 bg-zinc-900 p-4"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold">
                                            {item.title}
                                        </p>
                                        <p className="mt-1 text-xs text-zinc-400">
                                            {item.room || item.area}
                                        </p>
                                    </div>
                                    <Badge value={item.status} />
                                </div>
                                {item.status !== 'done' && (
                                    <CompleteChecklistForm item={item} />
                                )}
                            </article>
                        ))
                    )}
                </Section>

                <Section title="Check-in e check-out de hóspedes">
                    {reservations.map((reservation) => (
                        <ReservationCard
                            key={reservation.id}
                            reservation={reservation}
                        />
                    ))}
                </Section>

                <Section title="Reportar avarias">
                    <ReportForm rooms={rooms} />
                </Section>

                <Section title="Controlo Credelec">
                    <UtilityForm />
                </Section>

                <Section title="Requisição de produtos">
                    <RequisitionForm stockItems={stockItems} />
                </Section>
            </div>
        </main>
    );
}
