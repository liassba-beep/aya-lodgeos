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
        name: { pt: '4 quartos', en: '4 rooms' },
        description: { pt: 'Com casa de banho privativa e kitnet.', en: 'With private bathroom and kitchenette.' },
    },
    {
        name: { pt: 'Café da manhã', en: 'Breakfast' },
        description: { pt: 'Disponível para começar o dia com calma.', en: 'Available for an easy start to the day.' },
    },
    {
        name: { pt: 'Starlink Wi-Fi gratuito', en: 'Free Starlink Wi-Fi' },
        description: { pt: 'Internet estável para trabalho e lazer.', en: 'Stable internet for work and leisure.' },
    },
    {
        name: { pt: 'Parque privativo', en: 'Private parking' },
        description: { pt: 'Estacionamento reservado no alojamento.', en: 'Reserved parking at the property.' },
    },
    {
        name: { pt: 'DSTV', en: 'DSTV' },
        description: { pt: 'Entretenimento disponível nos quartos.', en: 'Entertainment available in the rooms.' },
    },
    {
        name: { pt: 'Ar condicionado', en: 'Air conditioning' },
        description: { pt: 'Conforto térmico durante a estadia.', en: 'Thermal comfort throughout your stay.' },
    },
    {
        name: { pt: 'Geleira', en: 'Fridge' },
        description: { pt: 'Apoio prático para estadias curtas ou prolongadas.', en: 'Practical support for short or longer stays.' },
    },
    {
        name: { pt: 'CCTV', en: 'CCTV' },
        description: { pt: 'Câmaras nas áreas públicas.', en: 'Cameras in public areas.' },
    },
    {
        name: { pt: 'Localização estratégica', en: 'Strategic location' },
        description: {
            pt: 'A poucos minutos do centro da cidade e das praias do Tofo e Barra.',
            en: 'A few minutes from the city centre and the Tofo and Barra beaches.',
        },
    },
];

const publicText = {
    pt: {
        navReserve: 'Reservar',
        navServices: 'Serviços',
        navPolicies: 'Políticas',
        navContacts: 'Contactos',
        heroTitle: 'O seu refúgio entre as praias de Inhambane e a cidade.',
        heroSubtitle: 'Guest house em Inhambane para estadias tranquilas, reservas directas e atendimento próximo.',
        checkAvailability: 'Consultar disponibilidade',
        accommodation: 'Alojamento',
        bookings: 'Reservas',
        onlineDirect: 'Online e directas',
        from: 'Desde',
        onRequest: 'Sob consulta',
        servicesEyebrow: 'Serviços',
        servicesTitle: 'Conforto simples, claro e bem localizado',
        servicesBody:
            'Estendemos as boas-vindas para que desfrute da melhor estadia. A MiKaya reúne comodidades essenciais para quem procura tranquilidade entre as praias e a cidade.',
        deposit: 'Depósito de reserva',
        cleaning: 'Limpeza',
        daily: 'Diária',
        cancellationPolicy: 'Política de cancelamento',
        houseRules: 'Regras da casa',
        fallbackPolicy: 'Informação a confirmar directamente com o alojamento.',
        bookingRequest: 'Fazer pedido de reserva',
        ownerArea: 'Área do proprietário',
    },
    en: {
        navReserve: 'Book',
        navServices: 'Services',
        navPolicies: 'Policies',
        navContacts: 'Contacts',
        heroTitle: 'Your refuge between Inhambane’s beaches and the city.',
        heroSubtitle: 'Guest house in Inhambane for peaceful stays, direct bookings and close hospitality.',
        checkAvailability: 'Check availability',
        accommodation: 'Property',
        bookings: 'Bookings',
        onlineDirect: 'Online and direct',
        from: 'From',
        onRequest: 'On request',
        servicesEyebrow: 'Services',
        servicesTitle: 'Simple comfort in a convenient location',
        servicesBody:
            'We welcome you to enjoy your best stay. MiKaya brings together essential comforts for travellers seeking calm between the beaches and the city.',
        deposit: 'Booking deposit',
        cleaning: 'Cleaning',
        daily: 'Daily',
        cancellationPolicy: 'Cancellation policy',
        houseRules: 'House rules',
        fallbackPolicy: 'Information to be confirmed directly with the property.',
        bookingRequest: 'Send booking request',
        ownerArea: 'Owner area',
    },
};

const bookingText = {
    pt: {
        eyebrow: 'Reservas directas',
        title: 'Confirme disponibilidade antes de enviar o pedido',
        body: 'Escolha as datas e informe os seus contactos. O preço estimado aparece automaticamente antes do envio.',
        estimatedPrice: 'Preço estimado',
        checking: 'A verificar...',
        nights: 'noite(s)',
        perNight: 'por noite',
        selectDates: 'Seleccione entrada e saída.',
        unavailable: 'Não foi possível verificar a disponibilidade. Tente novamente.',
        submitError: 'Não foi possível enviar o pedido.',
        name: 'Nome',
        phone: 'Telemóvel',
        email: 'Email',
        adults: 'Adultos',
        children: 'Crianças',
        checkIn: 'Entrada',
        checkOut: 'Saída',
        message: 'Mensagem',
        sending: 'A enviar...',
        submit: 'Enviar pedido de reserva',
    },
    en: {
        eyebrow: 'Direct bookings',
        title: 'Check availability before sending your request',
        body: 'Choose your dates and share your contact details. The estimated price appears automatically before sending.',
        estimatedPrice: 'Estimated price',
        checking: 'Checking...',
        nights: 'night(s)',
        perNight: 'per night',
        selectDates: 'Select check-in and check-out.',
        unavailable: 'Could not check availability. Please try again.',
        submitError: 'Could not send the request.',
        name: 'Name',
        phone: 'Phone',
        email: 'Email',
        adults: 'Adults',
        children: 'Children',
        checkIn: 'Check-in',
        checkOut: 'Check-out',
        message: 'Message',
        sending: 'Sending...',
        submit: 'Send booking request',
    },
};

export default function Property({ property, booking }) {
    const [locale, setLocale] = useState('pt');
    const text = publicText[locale];
    const phone = property.phone || '+258842990406';
    const email = property.email || 'reservas@lodgesos.com';
    const ownerLoginUrl = 'https://app.lodgesos.com/admin/login';
    const heroImage = '/images/mikaya-hero.jpg';

    const sectionLink = (sectionId) => (event) => {
        const target = document.getElementById(sectionId);

        if (!target) {
            return;
        }

        event.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.history.replaceState(null, '', `#${sectionId}`);
    };

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
                            <a
                                href="#topo"
                                onClick={sectionLink('topo')}
                                className="text-xl font-semibold tracking-wide"
                            >
                                {property.name}
                            </a>
                            <nav className="hidden items-center gap-8 text-sm text-white/80 md:flex">
                                <a href="#reservar" onClick={sectionLink('reservar')} className="hover:text-white">
                                    {text.navReserve}
                                </a>
                                <a href="#servicos" onClick={sectionLink('servicos')} className="hover:text-white">
                                    {text.navServices}
                                </a>
                                <a href="#politicas" onClick={sectionLink('politicas')} className="hover:text-white">
                                    {text.navPolicies}
                                </a>
                                <a href="#contactos" onClick={sectionLink('contactos')} className="hover:text-white">
                                    {text.navContacts}
                                </a>
                            </nav>
                            <div className="flex items-center gap-3">
                                <div className="flex rounded-full border border-white/15 bg-white/10 p-1 text-xs font-semibold">
                                    {['pt', 'en'].map((option) => (
                                        <button
                                            key={option}
                                            type="button"
                                            onClick={() => setLocale(option)}
                                            className={`rounded-full px-3 py-1 uppercase transition ${
                                                locale === option ? 'bg-white text-stone-950' : 'text-white/70 hover:text-white'
                                            }`}
                                        >
                                            {option}
                                        </button>
                                    ))}
                                </div>
                                <a
                                    href="#reservar"
                                    onClick={sectionLink('reservar')}
                                    className="rounded-full bg-amber-400 px-4 py-2 text-sm font-semibold text-black transition hover:bg-amber-300"
                                >
                                    {text.navReserve}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div
                        id="topo"
                        className="relative z-10 mx-auto flex min-h-[86vh] max-w-7xl scroll-mt-28 items-end px-6 pb-16 pt-36"
                    >
                        <div className="max-w-3xl">
                            <p className="mb-4 text-sm font-semibold uppercase tracking-[0.22em] text-amber-300">
                                {property.city || 'Inhambane'}, {property.country || 'Moçambique'}
                            </p>
                            <h1 className="max-w-4xl text-5xl font-bold leading-tight sm:text-6xl lg:text-7xl">
                                {text.heroTitle}
                            </h1>
                            <p className="mt-6 max-w-2xl text-lg leading-8 text-white/82">
                                {property.notes || text.heroSubtitle}
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <a
                                    href="#reservar"
                                    onClick={sectionLink('reservar')}
                                    className="rounded-full bg-amber-400 px-6 py-3 font-semibold text-black transition hover:bg-amber-300"
                                >
                                    {text.checkAvailability}
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="border-y border-white/10 bg-black px-6 py-6">
                    <div className="mx-auto grid max-w-7xl gap-4 sm:grid-cols-3">
                        <Metric label={text.accommodation} value={property.legal_name || property.name} />
                        <Metric label={text.bookings} value={text.onlineDirect} />
                        <Metric label={text.from} value={property.lowest_rate ? money(property.lowest_rate) : text.onRequest} />
                    </div>
                </section>

                <BookingSection property={property} booking={booking} locale={locale} />

                <section id="servicos" className="scroll-mt-28 bg-white px-6 py-20 text-stone-950">
                    <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.9fr_1.1fr]">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                                {text.servicesEyebrow}
                            </p>
                            <h2 className="mt-3 text-3xl font-bold">{text.servicesTitle}</h2>
                            <p className="mt-5 leading-7 text-stone-600">
                                {text.servicesBody}
                            </p>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Info label={text.deposit} value={`${property.deposit_percent || 50}%`} />
                            <Info label={text.cleaning} value={text.daily} />
                            {highlightServices.map((service) => (
                                <Info
                                    key={service.name.pt}
                                    label={service.name[locale]}
                                    value={service.description[locale]}
                                />
                            ))}
                        </div>
                    </div>
                </section>

                <section id="politicas" className="mx-auto grid max-w-7xl scroll-mt-28 gap-6 px-6 py-20 lg:grid-cols-2">
                    <Policy title={text.cancellationPolicy} text={property.cancellation_policy} fallback={text.fallbackPolicy} />
                    <Policy title={text.houseRules} text={property.house_rules} fallback={text.fallbackPolicy} />
                </section>

                <section id="contactos" className="scroll-mt-28 border-t border-white/10 bg-black px-6 py-14">
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
                                onClick={sectionLink('reservar')}
                                className="rounded-full bg-amber-400 px-6 py-3 text-center font-semibold text-black transition hover:bg-amber-300"
                            >
                                {text.bookingRequest}
                            </a>
                            <button
                                type="button"
                                onClick={goToOwnerLogin}
                                className="rounded-full border border-white/20 px-6 py-3 text-center font-semibold text-white transition hover:bg-white/10"
                            >
                                {text.ownerArea}
                            </button>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}

function BookingSection({ property, booking, locale }) {
    const text = bookingText[locale] || bookingText.pt;
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
                    setNotice({
                        type: 'error',
                        message: text.unavailable,
                    });
                }
            } finally {
                setChecking(false);
            }
        }, 350);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [booking.availability_url, canCheck, form.adults, form.check_in, form.check_out, form.children, text.unavailable]);

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
            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : {};

            if (!response.ok) {
                setErrors(data.errors || {});
                setNotice({ type: 'error', message: data.message || text.submitError });
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
        <section id="reservar" className="mx-auto max-w-7xl scroll-mt-28 px-6 py-20">
            <div className="grid gap-10 lg:grid-cols-[0.85fr_1.15fr]">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">
                        {text.eyebrow}
                    </p>
                    <h2 className="mt-3 text-3xl font-bold">{text.title}</h2>
                    <p className="mt-5 leading-7 text-white/68">
                        {text.body}
                    </p>
                    <div className="mt-8 rounded-lg border border-white/10 bg-white/[0.05] p-5">
                        <p className="text-sm text-white/55">{text.estimatedPrice}</p>
                        {checking ? (
                            <p className="mt-2 text-xl font-semibold text-amber-300">{text.checking}</p>
                        ) : availability?.available ? (
                            <div className="mt-2">
                                <p className="text-3xl font-bold text-amber-300">{money(availability.total)}</p>
                                <p className="mt-1 text-sm text-white/60">
                                    {availability.nights} {text.nights}, {money(availability.nightly_rate)} {text.perNight}.
                                </p>
                            </div>
                        ) : availability?.available === false ? (
                            <p className="mt-2 text-lg font-semibold text-red-300">{availability.message}</p>
                        ) : (
                            <p className="mt-2 text-lg font-semibold text-white/65">{text.selectDates}</p>
                        )}
                    </div>
                </div>

                <form onSubmit={submit} className="rounded-lg border border-white/10 bg-white/[0.06] p-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label={text.name} error={errors.guest_name}>
                            <input value={form.guest_name} onChange={update('guest_name')} required className="input" />
                        </Field>
                        <Field label={text.phone} error={errors.guest_phone}>
                            <input value={form.guest_phone} onChange={update('guest_phone')} required className="input" />
                        </Field>
                        <Field label={text.email} error={errors.guest_email}>
                            <input type="email" value={form.guest_email} onChange={update('guest_email')} className="input" />
                        </Field>
                        <Field label={text.adults} error={errors.adults}>
                            <input type="number" min="1" value={form.adults} onChange={update('adults')} required className="input" />
                        </Field>
                        <Field label={text.checkIn} error={errors.check_in}>
                            <input type="date" min={today} value={form.check_in} onChange={update('check_in')} required className="input" />
                        </Field>
                        <Field label={text.checkOut} error={errors.check_out}>
                            <input type="date" min={form.check_in || today} value={form.check_out} onChange={update('check_out')} required className="input" />
                        </Field>
                        <Field label={text.children} error={errors.children}>
                            <input type="number" min="0" value={form.children} onChange={update('children')} required className="input" />
                        </Field>
                    </div>
                    <Field label={text.message} error={errors.message} className="mt-4">
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
                        {submitting ? text.sending : text.submit}
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

function Policy({ title, text, fallback }) {
    return (
        <article className="rounded-lg border border-white/10 bg-white/[0.06] p-6">
            <h3 className="text-xl font-semibold">{title}</h3>
            <p className="mt-4 whitespace-pre-line leading-7 text-white/68">
                {text || fallback}
            </p>
        </article>
    );
}
