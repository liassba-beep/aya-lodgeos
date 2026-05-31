import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const money = (value) =>
    new Intl.NumberFormat('pt-MZ', {
        style: 'currency',
        currency: 'MZN',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

const today = new Date().toISOString().slice(0, 10);

const xsrfToken = () => {
    const token = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return token ? decodeURIComponent(token) : null;
};

const highlightServices = [
    {
        name: '4 quartos',
        description: 'Com casa de banho privativa e kitnet.',
    },
    {
        name: 'Café da manhã',
        description: 'Disponível para começar o dia com calma.',
    },
    {
        name: 'Starlink Wi-Fi gratuito',
        description: 'Internet estável para trabalho e lazer.',
    },
    {
        name: 'Parque privativo',
        description: 'Estacionamento reservado no alojamento.',
    },
    {
        name: 'DSTV',
        description: 'Entretenimento disponível nos quartos.',
    },
    {
        name: 'Ar condicionado',
        description: 'Conforto térmico durante a estadia.',
    },
    {
        name: 'Geleira',
        description: 'Apoio prático para estadias curtas ou prolongadas.',
    },
    {
        name: 'CCTV',
        description: 'Câmaras nas áreas públicas.',
    },
    {
        name: 'Localização estratégica',
        description: 'A poucos minutos do centro da cidade e das praias do Tofo e Barra.',
    },
];

export default function Property({ property, booking }) {
    const phone = property.phone || '+258842990406';
    const email = property.email || 'reservas@lodgesos.com';
    const ownerLoginUrl = 'https://app.lodgesos.com/admin/login';
    const heroImage = '/images/mikaya-hero.jpg';

    const goToOwnerLogin = () => {
        window.location.assign(ownerLoginUrl);
    };

    return (
        <>
            <Head title={`${property.name} · Reservas`} />
            <main className="min-h-screen bg-stone-950 text-white">
                <section className="relative min-h-[86vh] overflow-hidden">
                    <img
                        src={heroImage}
                        alt={`${property.name} em ${property.city || 'Moçambique'}`}
                        className="absolute inset-0 h-full w-full object-cover object-center"
                    />
                    <div className="absolute inset-0 bg-gradient-to-r from-black/90 via-black/55 to-black/10" />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/76 via-transparent to-black/24" />

                    <div className="absolute inset-x-0 top-0 z-10 border-b border-white/10 bg-black/35 backdrop-blur">
                        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                            <a href="#topo" className="text-xl font-semibold tracking-wide">
                                {property.name}
                            </a>
                            <nav className="hidden items-center gap-8 text-sm text-white/80 md:flex">
                                <a href="#reservar" className="hover:text-white">
                                    Reservar
                                </a>
                                <a href="#servicos" className="hover:text-white">
                                    Serviços
                                </a>
                                <a href="#politicas" className="hover:text-white">
                                    Políticas
                                </a>
                                <a href="#contactos" className="hover:text-white">
                                    Contactos
                                </a>
                            </nav>
                            <div className="flex items-center gap-2">
                                <a
                                    href="#reservar"
                                    className="rounded-full bg-amber-400 px-4 py-2 text-sm font-semibold text-black transition hover:bg-amber-300"
                                >
                                    Reservar
                                </a>
                            </div>
                        </div>
                    </div>

                    <div
                        id="topo"
                        className="relative z-10 mx-auto flex min-h-[86vh] max-w-7xl items-end px-6 pb-16 pt-36"
                    >
                        <div className="max-w-3xl">
                            <p className="mb-4 text-sm font-semibold uppercase tracking-[0.22em] text-amber-300">
                                {property.city || 'Inhambane'}, {property.country || 'Moçambique'}
                            </p>
                            <h1 className="max-w-4xl text-5xl font-bold leading-tight sm:text-6xl lg:text-7xl">
                                O seu refúgio entre as praias de Inhambane e a cidade.
                            </h1>
                            <p className="mt-6 max-w-2xl text-lg leading-8 text-white/82">
                                {property.notes ||
                                    'Guest house em Inhambane para estadias tranquilas, reservas directas e atendimento próximo.'}
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <a
                                    href="#reservar"
                                    className="rounded-full bg-amber-400 px-6 py-3 font-semibold text-black transition hover:bg-amber-300"
                                >
                                    Consultar disponibilidade
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="border-y border-white/10 bg-black px-6 py-6">
                    <div className="mx-auto grid max-w-7xl gap-4 sm:grid-cols-3">
                        <Metric label="Alojamento" value={property.legal_name || property.name} />
                        <Metric label="Reservas" value="Online e directas" />
                        <Metric label="Desde" value={property.lowest_rate ? money(property.lowest_rate) : 'Sob consulta'} />
                    </div>
                </section>

                <BookingSection property={property} booking={booking} />

                <section id="servicos" className="bg-white px-6 py-20 text-stone-950">
                    <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.9fr_1.1fr]">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                                Serviços
                            </p>
                            <h2 className="mt-3 text-3xl font-bold">Conforto simples, claro e bem localizado</h2>
                            <p className="mt-5 leading-7 text-stone-600">
                                Estendemos as boas-vindas para que desfrute da melhor estadia. A MiKaya reúne
                                comodidades essenciais para quem procura tranquilidade entre as praias e a cidade.
                            </p>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Info label="Depósito de reserva" value={`${property.deposit_percent || 50}%`} />
                            <Info label="Limpeza" value="Diária" />
                            {highlightServices.map((service) => (
                                <Info
                                    key={service.name}
                                    label={service.name}
                                    value={service.description || 'Disponível'}
                                />
                            ))}
                        </div>
                    </div>
                </section>

                <section id="politicas" className="mx-auto grid max-w-7xl gap-6 px-6 py-20 lg:grid-cols-2">
                    <Policy title="Política de cancelamento" text={property.cancellation_policy} />
                    <Policy title="Regras da casa" text={property.house_rules} />
                </section>

                <section id="contactos" className="border-t border-white/10 bg-black px-6 py-14">
                    <div className="mx-auto flex max-w-7xl flex-col justify-between gap-8 md:flex-row md:items-center">
                        <div>
                            <h2 className="text-2xl font-bold">{property.name}</h2>
                            <p className="mt-2 text-white/65">
                                {property.address || property.city || 'Moçambique'}
                            </p>
                            <p className="mt-1 text-white/65">{phone}</p>
                            <p className="mt-1 text-white/65">{email}</p>
                        </div>
                        <div className="flex w-full flex-col gap-3 sm:flex-row md:w-auto">
                            <a
                                href="#reservar"
                                className="rounded-full bg-amber-400 px-6 py-3 text-center font-semibold text-black transition hover:bg-amber-300"
                            >
                                Fazer pedido de reserva
                            </a>
                            <button
                                type="button"
                                onClick={goToOwnerLogin}
                                className="rounded-full border border-white/20 px-6 py-3 text-center font-semibold text-white transition hover:bg-white/10"
                            >
                                Área do proprietário
                            </button>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}

function BookingSection({ property, booking }) {
    const [form, setForm] = useState({
        guest_name: '',
        guest_phone: '',
        guest_email: '',
        check_in: '',
        check_out: '',
        adults: 1,
        children: 0,
        message: '',
    });
    const [availability, setAvailability] = useState(null);
    const [checking, setChecking] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [notice, setNotice] = useState(null);
    const [errors, setErrors] = useState({});

    const canCheck = useMemo(
        () => form.check_in && form.check_out && form.adults,
        [form.check_in, form.check_out, form.adults],
    );

    useEffect(() => {
        if (!canCheck) {
            setAvailability(null);
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setChecking(true);
            setNotice(null);
            const params = new URLSearchParams({
                check_in: form.check_in,
                check_out: form.check_out,
                adults: String(form.adults || 1),
                children: String(form.children || 0),
            });

            try {
                const response = await fetch(`${booking.availability_url}?${params}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                const data = await response.json();
                setAvailability(data);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setAvailability(null);
                }
            } finally {
                setChecking(false);
            }
        }, 350);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [booking.availability_url, canCheck, form.adults, form.check_in, form.check_out, form.children]);

    const update = (field) => (event) => {
        setForm((current) => ({ ...current, [field]: event.target.value }));
        setErrors((current) => ({ ...current, [field]: null }));
    };

    const submit = async (event) => {
        event.preventDefault();
        setSubmitting(true);
        setNotice(null);
        setErrors({});

        try {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');
            const xsrf = xsrfToken();
            const response = await fetch(booking.store_url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
                },
                body: JSON.stringify(form),
            });
            const data = await response.json();

            if (!response.ok) {
                setErrors(data.errors || {});
                setNotice({ type: 'error', message: data.message || 'Não foi possível enviar o pedido.' });
                return;
            }

            setNotice({ type: 'success', message: data.message });
            setForm({
                guest_name: '',
                guest_phone: '',
                guest_email: '',
                check_in: '',
                check_out: '',
                adults: 1,
                children: 0,
                message: '',
            });
            setAvailability(null);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <section id="reservar" className="mx-auto max-w-7xl px-6 py-20">
            <div className="grid gap-10 lg:grid-cols-[0.85fr_1.15fr]">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">
                        Reservas directas
                    </p>
                    <h2 className="mt-3 text-3xl font-bold">Confirme disponibilidade antes de enviar o pedido</h2>
                    <p className="mt-5 leading-7 text-white/68">
                        Escolha as datas e informe os seus contactos. O preço estimado aparece automaticamente
                        antes do envio.
                    </p>
                    <div className="mt-8 rounded-lg border border-white/10 bg-white/[0.05] p-5">
                        <p className="text-sm text-white/55">Preço estimado</p>
                        {checking ? (
                            <p className="mt-2 text-xl font-semibold text-amber-300">A verificar...</p>
                        ) : availability?.available ? (
                            <div className="mt-2">
                                <p className="text-3xl font-bold text-amber-300">{money(availability.total)}</p>
                                <p className="mt-1 text-sm text-white/60">
                                    {availability.nights} noite(s), {money(availability.nightly_rate)} por noite.
                                </p>
                            </div>
                        ) : availability?.available === false ? (
                            <p className="mt-2 text-lg font-semibold text-red-300">{availability.message}</p>
                        ) : (
                            <p className="mt-2 text-lg font-semibold text-white/65">Seleccione entrada e saída.</p>
                        )}
                    </div>
                </div>

                <form onSubmit={submit} className="rounded-lg border border-white/10 bg-white/[0.06] p-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Nome" error={errors.guest_name}>
                            <input value={form.guest_name} onChange={update('guest_name')} required className="input" />
                        </Field>
                        <Field label="Telemóvel" error={errors.guest_phone}>
                            <input value={form.guest_phone} onChange={update('guest_phone')} required className="input" />
                        </Field>
                        <Field label="Email" error={errors.guest_email}>
                            <input type="email" value={form.guest_email} onChange={update('guest_email')} className="input" />
                        </Field>
                        <Field label="Adultos" error={errors.adults}>
                            <input type="number" min="1" value={form.adults} onChange={update('adults')} required className="input" />
                        </Field>
                        <Field label="Entrada" error={errors.check_in}>
                            <input type="date" min={today} value={form.check_in} onChange={update('check_in')} required className="input" />
                        </Field>
                        <Field label="Saída" error={errors.check_out}>
                            <input type="date" min={form.check_in || today} value={form.check_out} onChange={update('check_out')} required className="input" />
                        </Field>
                        <Field label="Crianças" error={errors.children}>
                            <input type="number" min="0" value={form.children} onChange={update('children')} required className="input" />
                        </Field>
                    </div>
                    <Field label="Mensagem" error={errors.message} className="mt-4">
                        <textarea value={form.message} onChange={update('message')} rows="4" className="input resize-none" />
                    </Field>
                    {notice && (
                        <div
                            className={`mt-4 rounded-lg border px-4 py-3 text-sm ${
                                notice.type === 'success'
                                    ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-100'
                                    : 'border-red-400/40 bg-red-400/10 text-red-100'
                            }`}
                        >
                            {notice.message}
                        </div>
                    )}
                    <button
                        type="submit"
                        disabled={submitting || availability?.available === false}
                        className="mt-6 w-full rounded-full bg-amber-400 px-6 py-3 font-semibold text-black transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {submitting ? 'A enviar...' : 'Enviar pedido de reserva'}
                    </button>
                </form>
            </div>
        </section>
    );
}

function Field({ label, error, className = '', children }) {
    return (
        <label className={`block ${className}`}>
            <span className="text-sm font-semibold text-white/78">{label}</span>
            <div className="mt-2">{children}</div>
            {error && <span className="mt-1 block text-sm text-red-300">{Array.isArray(error) ? error[0] : error}</span>}
        </label>
    );
}

function Metric({ label, value }) {
    return (
        <div className="rounded-lg border border-white/10 bg-white/[0.04] p-5">
            <p className="text-sm text-white/50">{label}</p>
            <p className="mt-2 text-xl font-semibold">{value}</p>
        </div>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-lg border border-stone-200 bg-stone-50 p-5">
            <p className="text-sm font-semibold text-stone-500">{label}</p>
            <p className="mt-2 text-lg font-bold">{value}</p>
        </div>
    );
}

function Policy({ title, text }) {
    return (
        <article className="rounded-lg border border-white/10 bg-white/[0.06] p-6">
            <h3 className="text-xl font-semibold">{title}</h3>
            <p className="mt-4 whitespace-pre-line leading-7 text-white/68">
                {text || 'Informação a confirmar directamente com o alojamento.'}
            </p>
        </article>
    );
}
