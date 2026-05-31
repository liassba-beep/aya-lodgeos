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

const phoneHref = (phone) => {
    const digits = String(phone || '').replace(/[^\d+]/g, '');

    return digits ? `tel:${digits}` : null;
};

const cleaningIntervalText = (days) => {
    const interval = Number(days || 0);

    if (interval === 1) {
        return text.daily;
    }

    return interval > 1 ? `A cada ${interval} dias` : text.daily;
};

const imageUrl = (path) => {
    if (!path) {
        return null;
    }

    if (path.startsWith('http') || path.startsWith('/')) {
        return path;
    }

    return `/storage/${path}`;
};

const text = {
    reserve: 'Reservar',
    gallery: 'Galeria',
    rooms: 'Quartos',
    location: 'Localização',
    contacts: 'Contactos',
    heroFallback: 'Reservas directas, contacto próximo e informação clara para planear a estadia.',
    checkAvailability: 'Consultar disponibilidade',
    from: 'Desde',
    directBookings: 'Reservas directas',
    photosTitle: 'Veja antes de reservar',
    photosBody: 'As fotos são geridas pelo proprietário e ajudam o hóspede a perceber exactamente o que vai encontrar.',
    roomsTitle: 'Quartos e expectativas',
    roomsBody: 'Tipos de quarto configurados pelo alojamento, com preço base, capacidade e comodidades incluídas.',
    includes: 'Inclui',
    guests: 'hóspedes',
    services: 'Serviços e condições',
    deposit: 'Depósito de reserva',
    cleaning: 'Limpeza',
    daily: 'Diária',
    policies: 'Políticas',
    fallbackPolicy: 'Informação a confirmar directamente com o alojamento.',
    locationBody: 'Use o mapa e os pontos de referência para confirmar a zona antes de viajar.',
    nearby: 'Perto de',
    openInGoogleMaps: 'Abrir no Google Maps',
    call: 'Ligar',
    whatsapp: 'WhatsApp',
    ownerArea: 'Área do proprietário',
    testimonials: 'Testemunhos',
};

const bookingText = {
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
    continueWhatsapp: 'Continuar no WhatsApp',
};

export default function Property({ tenant, property, website = {}, booking }) {
    const phone = property.phone || null;
    const email = property.email || null;
    const heroImage = imageUrl(website.hero_image) || imageUrl(website.photos?.[0]?.src);
    const roomRates = (website.room_types || []).map((room) => Number(room.price_from || 0)).filter(Boolean);
    const lowestRate = roomRates.length ? Math.min(...roomRates) : property.lowest_rate;
    const languages = (property.services || []).find((service) => service.name === 'Idiomas')?.description;

    return (
        <>
            <Head title={website.title || property.name}>
                {website.description && <meta name="description" content={website.description} />}
                {website.canonical_url && <link rel="canonical" href={website.canonical_url} />}
                {website.favicon && <link rel="icon" href={website.favicon} />}
                <meta property="og:type" content="business.business" />
                <meta property="og:locale" content="pt_PT" />
                <meta property="og:title" content={website.title || property.name} />
                {website.description && <meta property="og:description" content={website.description} />}
                {website.og_image && <meta property="og:image" content={website.og_image} />}
                {website.canonical_url && <meta property="og:url" content={website.canonical_url} />}
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={website.title || property.name} />
                {website.description && <meta name="twitter:description" content={website.description} />}
                {website.og_image && <meta name="twitter:image" content={website.og_image} />}
                {website.json_ld && (
                    <script type="application/ld+json">
                        {JSON.stringify(website.json_ld)}
                    </script>
                )}
            </Head>

            <main className="min-h-screen bg-stone-950 text-white">
                {website.whatsapp_url && (
                    <a
                        href={website.whatsapp_url}
                        target="_blank"
                        rel="noreferrer"
                        className="fixed bottom-5 right-5 z-50 rounded-full bg-emerald-400 px-5 py-3 text-sm font-bold text-emerald-950 shadow-2xl shadow-emerald-950/40 transition hover:bg-emerald-300"
                    >
                        {text.whatsapp}
                    </a>
                )}

                <section className="relative min-h-[86vh] overflow-hidden">
                    {heroImage ? (
                        <picture>
                            {website.hero_srcset?.avif && <source type="image/avif" srcSet={website.hero_srcset.avif} />}
                            {website.hero_srcset?.webp && <source type="image/webp" srcSet={website.hero_srcset.webp} />}
                            <img
                                src={heroImage}
                                alt={`${property.name} em ${property.city || property.country || 'Moçambique'}`}
                                className="absolute inset-0 h-full w-full object-cover object-center"
                                fetchPriority="high"
                                sizes="100vw"
                            />
                        </picture>
                    ) : (
                        <div className="absolute inset-0 bg-stone-900" />
                    )}
                    <div className="absolute inset-0 bg-gradient-to-r from-black/88 via-black/58 to-black/16" />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/25" />

                    <Header property={property} />

                    <div id="topo" className="relative z-10 mx-auto flex min-h-[86vh] max-w-7xl scroll-mt-28 items-end px-6 pb-16 pt-36">
                        <div className="max-w-3xl">
                            <p className="mb-4 text-sm font-semibold uppercase tracking-[0.22em] text-amber-300">
                                {[property.city, property.country].filter(Boolean).join(', ') || tenant?.name}
                            </p>
                            <h1 className="max-w-4xl text-5xl font-bold leading-tight sm:text-6xl lg:text-7xl">
                                {property.legal_name || property.name}
                            </h1>
                            <p className="mt-6 max-w-2xl text-lg leading-8 text-white/82">
                                {website.description || property.notes || text.heroFallback}
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <a href="#reservar" className="rounded-full bg-amber-400 px-6 py-3 font-semibold text-black transition hover:bg-amber-300">
                                    {text.checkAvailability}
                                </a>
                                {website.whatsapp_url && (
                                    <a href={website.whatsapp_url} target="_blank" rel="noreferrer" className="rounded-full border border-white/25 px-6 py-3 font-semibold text-white transition hover:bg-white/10">
                                        {text.whatsapp}
                                    </a>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="border-y border-white/10 bg-black px-6 py-6">
                    <div className="mx-auto grid max-w-7xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Metric label="Alojamento" value={property.legal_name || property.name} />
                        <Metric label={text.directBookings} value={property.city || tenant?.name} />
                        <Metric label={text.from} value={lowestRate ? money(lowestRate) : 'Sob consulta'} />
                        {languages && <Metric label="Idiomas" value={languages} />}
                    </div>
                </section>

                <BookingSection property={property} booking={booking} />

                <Gallery photos={website.photos || []} />
                <RoomTypes rooms={website.room_types || []} depositPercent={property.deposit_percent || 50} />
                <Services property={property} />
                <Location property={property} website={website} />
                <Testimonials testimonials={website.testimonials || []} />
                <Policies property={property} />
                <Contact property={property} website={website} phone={phone} email={email} />
            </main>
        </>
    );
}

function Header({ property }) {
    return (
        <div className="absolute inset-x-0 top-0 z-30 border-b border-white/10 bg-black/35 backdrop-blur">
            <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                <a href="#topo" className="text-xl font-semibold tracking-wide">
                    {property.name}
                </a>
                <nav className="hidden items-center gap-8 text-sm text-white/80 md:flex">
                    <a href="#reservar" className="hover:text-white">{text.reserve}</a>
                    <a href="#galeria" className="hover:text-white">{text.gallery}</a>
                    <a href="#quartos" className="hover:text-white">{text.rooms}</a>
                    <a href="#localizacao" className="hover:text-white">{text.location}</a>
                    <a href="#contactos" className="hover:text-white">{text.contacts}</a>
                </nav>
                <a href="#reservar" className="rounded-full bg-amber-400 px-4 py-2 text-sm font-semibold text-black transition hover:bg-amber-300">
                    {text.reserve}
                </a>
            </div>
        </div>
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
                    setNotice({ type: 'error', message: bookingText.unavailable });
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
                setNotice({ type: 'error', message: data.message || bookingText.submitError });
                return;
            }

            setNotice({
                type: 'success',
                message: data.message,
                whatsappUrl: data.whatsapp_url,
            });
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
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">{bookingText.eyebrow}</p>
                    <h2 className="mt-3 text-3xl font-bold">{bookingText.title}</h2>
                    <p className="mt-5 leading-7 text-white/68">{bookingText.body}</p>
                    <div className="mt-8 rounded-lg border border-white/10 bg-white/[0.05] p-5">
                        <p className="text-sm text-white/55">{bookingText.estimatedPrice}</p>
                        {checking ? (
                            <p className="mt-2 text-xl font-semibold text-amber-300">{bookingText.checking}</p>
                        ) : availability?.available ? (
                            <div className="mt-2">
                                <p className="text-3xl font-bold text-amber-300">{money(availability.total)}</p>
                                <p className="mt-1 text-sm text-white/60">
                                    {availability.nights} {bookingText.nights}, {money(availability.nightly_rate)} {bookingText.perNight}.
                                </p>
                            </div>
                        ) : availability?.available === false ? (
                            <p className="mt-2 text-lg font-semibold text-red-300">{availability.message}</p>
                        ) : (
                            <p className="mt-2 text-lg font-semibold text-white/65">{bookingText.selectDates}</p>
                        )}
                    </div>
                    <p className="mt-4 text-sm leading-6 text-white/52">
                        A reserva só fica garantida após confirmação do alojamento e depósito de {property.deposit_percent || 50}%.
                    </p>
                </div>

                <form onSubmit={submit} className="rounded-lg border border-white/10 bg-white/[0.06] p-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label={bookingText.name} error={errors.guest_name}>
                            <input value={form.guest_name} onChange={update('guest_name')} required className="input" />
                        </Field>
                        <Field label={bookingText.phone} error={errors.guest_phone}>
                            <input value={form.guest_phone} onChange={update('guest_phone')} required className="input" />
                        </Field>
                        <Field label={bookingText.email} error={errors.guest_email}>
                            <input type="email" value={form.guest_email} onChange={update('guest_email')} className="input" />
                        </Field>
                        <Field label={bookingText.adults} error={errors.adults}>
                            <input type="number" min="1" value={form.adults} onChange={update('adults')} required className="input" />
                        </Field>
                        <Field label={bookingText.checkIn} error={errors.check_in}>
                            <input type="date" min={today} value={form.check_in} onChange={update('check_in')} required className="input" />
                        </Field>
                        <Field label={bookingText.checkOut} error={errors.check_out}>
                            <input type="date" min={form.check_in || today} value={form.check_out} onChange={update('check_out')} required className="input" />
                        </Field>
                        <Field label={bookingText.children} error={errors.children}>
                            <input type="number" min="0" value={form.children} onChange={update('children')} required className="input" />
                        </Field>
                    </div>
                    <Field label={bookingText.message} error={errors.message} className="mt-4">
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
                            <p>{notice.message}</p>
                            {notice.whatsappUrl && (
                                <a href={notice.whatsappUrl} target="_blank" rel="noreferrer" className="mt-3 inline-flex rounded-full bg-emerald-400 px-4 py-2 font-semibold text-emerald-950">
                                    {bookingText.continueWhatsapp}
                                </a>
                            )}
                        </div>
                    )}
                    <button
                        type="submit"
                        disabled={submitting || availability?.available === false}
                        className="mt-6 w-full rounded-full bg-amber-400 px-6 py-3 font-semibold text-black transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {submitting ? bookingText.sending : bookingText.submit}
                    </button>
                </form>
            </div>
        </section>
    );
}

function Gallery({ photos }) {
    const [active, setActive] = useState(null);

    if (!photos.length) {
        return null;
    }

    return (
        <section id="galeria" className="scroll-mt-28 bg-white px-6 py-20 text-stone-950">
            <div className="mx-auto max-w-7xl">
                <div className="max-w-2xl">
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">{text.gallery}</p>
                    <h2 className="mt-3 text-3xl font-bold">{text.photosTitle}</h2>
                    <p className="mt-5 leading-7 text-stone-600">{text.photosBody}</p>
                </div>
                <div className="mt-10 grid gap-4 md:grid-cols-3">
                    {photos.map((photo, index) => (
                        <button key={photo.id || photo.src} type="button" onClick={() => setActive(index)} className={index === 0 ? 'md:col-span-2 md:row-span-2' : ''}>
                            <ResponsiveImage image={photo} alt={photo.alt} className="aspect-[4/3] h-full w-full rounded-lg object-cover" />
                            {photo.caption && <span className="mt-2 block text-left text-sm text-stone-500">{photo.caption}</span>}
                        </button>
                    ))}
                </div>
            </div>
            {active !== null && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-5" role="dialog" aria-modal="true">
                    <button type="button" onClick={() => setActive(null)} className="absolute right-5 top-5 rounded-full border border-white/30 px-4 py-2 text-white">
                        Fechar
                    </button>
                    <ResponsiveImage image={photos[active]} alt={photos[active].alt} className="max-h-[82vh] max-w-[92vw] rounded-lg object-contain" eager />
                </div>
            )}
        </section>
    );
}

function RoomTypes({ rooms, depositPercent }) {
    if (!rooms.length) {
        return null;
    }

    return (
        <section id="quartos" className="mx-auto max-w-7xl scroll-mt-28 px-6 py-20">
            <div className="max-w-2xl">
                <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">{text.rooms}</p>
                <h2 className="mt-3 text-3xl font-bold">{text.roomsTitle}</h2>
                <p className="mt-5 leading-7 text-white/68">{text.roomsBody}</p>
            </div>
            <div className="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                {rooms.map((room) => (
                    <article key={room.id || room.name} className="overflow-hidden rounded-lg border border-white/10 bg-white/[0.05]">
                        {room.photo && <ResponsiveImage image={{ src: room.photo, srcset: room.srcset }} alt={room.name} className="aspect-[4/3] w-full object-cover" />}
                        <div className="p-5">
                            <h3 className="text-xl font-bold">{room.name}</h3>
                            <p className="mt-2 text-sm text-white/60">{room.capacity} {text.guests}</p>
                            {room.description && <p className="mt-4 text-sm leading-6 text-white/70">{room.description}</p>}
                            <p className="mt-5 text-2xl font-bold text-amber-300">{money(room.price_from)}</p>
                            <p className="mt-1 text-xs text-white/45">Depósito de {depositPercent}% para garantir.</p>
                            {!!room.amenities?.length && (
                                <div className="mt-5">
                                    <p className="text-sm font-semibold text-white/78">{text.includes}</p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {room.amenities.map((amenity) => (
                                            <span key={amenity} className="rounded-full border border-white/15 px-3 py-1 text-xs text-white/70">
                                                {amenity}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}

function Services({ property }) {
    const services = property.services || [];

    return (
        <section className="bg-stone-100 px-6 py-20 text-stone-950">
            <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.9fr_1.1fr]">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">{text.services}</p>
                    <h2 className="mt-3 text-3xl font-bold">Informação útil antes da chegada</h2>
                    <p className="mt-5 leading-7 text-stone-600">
                        Condições configuradas pelo alojamento para alinhar expectativas antes da reserva.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Info label={text.deposit} value={`${property.deposit_percent || 50}%`} />
                    <Info label={text.cleaning} value={cleaningIntervalText(property.cleaning_interval_days)} />
                    {services.map((service) => (
                        <Info key={service.name} label={service.name} value={service.description || 'Disponível'} />
                    ))}
                </div>
            </div>
        </section>
    );
}

function Location({ property, website }) {
    useEffect(() => {
        if (!website.latitude || !website.longitude || !document.getElementById('tenant-map')) {
            return;
        }

        const loadLeaflet = async () => {
            if (!document.querySelector('link[data-leaflet]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                link.dataset.leaflet = 'true';
                document.head.appendChild(link);
            }

            if (!window.L) {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.body.appendChild(script);
                });
            }

            const node = document.getElementById('tenant-map');
            if (!node || node.dataset.ready) {
                return;
            }

            node.dataset.ready = 'true';
            const map = window.L.map(node, { scrollWheelZoom: false }).setView([website.latitude, website.longitude], 13);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);
            window.L.marker([website.latitude, website.longitude]).addTo(map).bindPopup(property.name);
        };

        loadLeaflet().catch(() => {});
    }, [property.name, website.latitude, website.longitude]);

    if (!website.latitude || !website.longitude) {
        return null;
    }

    return (
        <section id="localizacao" className="scroll-mt-28 bg-white px-6 py-20 text-stone-950">
            <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.1fr_0.9fr]">
                <div id="tenant-map" className="min-h-[420px] rounded-lg bg-stone-200" />
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">{text.location}</p>
                    <h2 className="mt-3 text-3xl font-bold">{website.address_label || property.address || property.city}</h2>
                    <p className="mt-5 leading-7 text-stone-600">{website.directions_note || text.locationBody}</p>
                    {website.google_maps_url && (
                        <a
                            href={website.google_maps_url}
                            target="_blank"
                            rel="noreferrer"
                            className="mt-5 inline-flex rounded-full bg-amber-400 px-5 py-3 text-sm font-semibold text-black transition hover:bg-amber-300"
                        >
                            {text.openInGoogleMaps}
                        </a>
                    )}
                    {!!website.nearby?.length && (
                        <div className="mt-8 space-y-3">
                            <h3 className="font-semibold">{text.nearby}</h3>
                            {website.nearby.map((item) => (
                                <div key={item.name} className="flex items-center justify-between border-b border-stone-200 pb-3">
                                    <span>{item.name}</span>
                                    <span className="text-stone-500">{item.distance}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}

function Testimonials({ testimonials }) {
    if (!testimonials.length) {
        return null;
    }

    return (
        <section className="mx-auto max-w-7xl px-6 py-20">
            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">{text.testimonials}</p>
            <div className="mt-8 grid gap-5 md:grid-cols-3">
                {testimonials.map((testimonial) => (
                    <article key={testimonial.id} className="rounded-lg border border-white/10 bg-white/[0.05] p-6">
                        <p className="leading-7 text-white/75">{testimonial.text}</p>
                        <p className="mt-5 font-semibold">{testimonial.author}</p>
                        {testimonial.source && <p className="text-sm text-white/45">{testimonial.source}</p>}
                    </article>
                ))}
            </div>
        </section>
    );
}

function Policies({ property }) {
    return (
        <section id="politicas" className="mx-auto grid max-w-7xl scroll-mt-28 gap-6 px-6 py-20 lg:grid-cols-2">
            <Policy title="Política de cancelamento" text={property.cancellation_policy} fallback={text.fallbackPolicy} />
            <Policy title="Regras da casa" text={property.house_rules} fallback={text.fallbackPolicy} />
        </section>
    );
}

function Contact({ property, website, phone, email }) {
    return (
        <section id="contactos" className="scroll-mt-28 border-t border-white/10 bg-black px-6 py-14">
            <div className="mx-auto flex max-w-7xl flex-col justify-between gap-8 md:flex-row md:items-center">
                <div>
                    <h2 className="text-2xl font-bold">{property.name}</h2>
                    <p className="mt-2 text-white/65">{website.address_label || property.address || property.city || property.country}</p>
                    {phone && (
                        <a className="mt-1 block text-white/65 hover:text-white" href={phoneHref(phone) || undefined}>
                            {phone}
                        </a>
                    )}
                    {email && <a className="mt-1 block text-white/65 hover:text-white" href={`mailto:${email}`}>{email}</a>}
                    <a className="mt-5 inline-block text-sm text-white/35 hover:text-white/70" href="https://app.lodgesos.com/admin/login">
                        {text.ownerArea}
                    </a>
                </div>
                <div className="flex w-full flex-col gap-3 sm:flex-row md:w-auto">
                    {phone && (
                        <a href={phoneHref(phone) || '#'} className="rounded-full border border-white/20 px-6 py-3 text-center font-semibold text-white transition hover:bg-white/10">
                            {text.call}
                        </a>
                    )}
                    {website.whatsapp_url && (
                        <a href={website.whatsapp_url} target="_blank" rel="noreferrer" className="rounded-full bg-emerald-400 px-6 py-3 text-center font-semibold text-emerald-950 transition hover:bg-emerald-300">
                            {text.whatsapp}
                        </a>
                    )}
                    <a href="#reservar" className="rounded-full bg-amber-400 px-6 py-3 text-center font-semibold text-black transition hover:bg-amber-300">
                        {text.reserve}
                    </a>
                </div>
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

function ResponsiveImage({ image, alt, className, eager = false }) {
    const src = imageUrl(image?.src);

    if (!src) {
        return null;
    }

    return (
        <picture>
            {image?.srcset?.avif && <source type="image/avif" srcSet={image.srcset.avif} />}
            {image?.srcset?.webp && <source type="image/webp" srcSet={image.srcset.webp} />}
            <img src={src} alt={alt} loading={eager ? 'eager' : 'lazy'} sizes="(min-width: 768px) 33vw, 100vw" className={className} />
        </picture>
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
        <div className="rounded-lg border border-stone-200 bg-white p-5">
            <p className="text-sm font-semibold text-stone-500">{label}</p>
            <p className="mt-2 text-lg font-bold">{value}</p>
        </div>
    );
}

function Policy({ title, text: body, fallback }) {
    return (
        <article className="rounded-lg border border-white/10 bg-white/[0.05] p-6">
            <h3 className="text-xl font-bold">{title}</h3>
            <div className="mt-4 whitespace-pre-line text-sm leading-7 text-white/68">
                {body || fallback}
            </div>
        </article>
    );
}
