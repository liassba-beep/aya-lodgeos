import { Head } from '@inertiajs/react';

const money = (value) =>
    new Intl.NumberFormat('pt-MZ', {
        style: 'currency',
        currency: 'MZN',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

const roomType = {
    single: 'Individual',
    double: 'Duplo',
    twin: 'Twin',
    suite: 'Suite',
    standard: 'Standard',
};

export default function Property({ property }) {
    const phone = property.phone || '+258842990406';
    const email = property.email || 'reservas@lodgesos.com';
    const whatsapp = `https://wa.me/${phone.replace(/\D/g, '')}?text=${encodeURIComponent(
        `Olá, gostaria de consultar disponibilidade em ${property.name}.`,
    )}`;
    const heroImage =
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1800&q=85';

    return (
        <>
            <Head title={`${property.name} · Reservas`} />
            <main className="min-h-screen bg-stone-950 text-white">
                <section className="relative min-h-[88vh] overflow-hidden">
                    <img
                        src={heroImage}
                        alt={`${property.name} em ${property.city || 'Moçambique'}`}
                        className="absolute inset-0 h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 bg-gradient-to-r from-black/90 via-black/62 to-black/18" />
                    <div className="absolute inset-x-0 top-0 z-10 border-b border-white/10 bg-black/30 backdrop-blur">
                        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                            <a href="#topo" className="text-xl font-semibold tracking-wide">
                                {property.name}
                            </a>
                            <nav className="hidden items-center gap-8 text-sm text-white/80 md:flex">
                                <a href="#quartos" className="hover:text-white">
                                    Quartos
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
                            <a
                                href={whatsapp}
                                className="rounded-full bg-amber-400 px-4 py-2 text-sm font-semibold text-black transition hover:bg-amber-300"
                            >
                                Reservar
                            </a>
                        </div>
                    </div>

                    <div
                        id="topo"
                        className="relative z-10 mx-auto flex min-h-[88vh] max-w-7xl items-end px-6 pb-16 pt-36"
                    >
                        <div className="max-w-3xl">
                            <p className="mb-4 text-sm font-semibold uppercase tracking-[0.22em] text-amber-300">
                                {property.city || 'Inhambane'}, {property.country || 'Moçambique'}
                            </p>
                            <h1 className="max-w-4xl text-5xl font-bold leading-tight sm:text-6xl lg:text-7xl">
                                Estadia tranquila, gestão cuidada e reservas directas.
                            </h1>
                            <p className="mt-6 max-w-2xl text-lg leading-8 text-white/78">
                                {property.notes ||
                                    'Uma guest house preparada para receber hóspedes com conforto, proximidade e serviço atento.'}
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <a
                                    href={whatsapp}
                                    className="rounded-full bg-amber-400 px-6 py-3 font-semibold text-black transition hover:bg-amber-300"
                                >
                                    Consultar disponibilidade
                                </a>
                                <a
                                    href={`mailto:${email}`}
                                    className="rounded-full border border-white/25 px-6 py-3 font-semibold text-white transition hover:bg-white/10"
                                >
                                    Enviar email
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="border-y border-white/10 bg-black px-6 py-6">
                    <div className="mx-auto grid max-w-7xl gap-4 sm:grid-cols-3">
                        <Metric label="Alojamento" value={property.legal_name || property.name} />
                        <Metric label="Quartos activos" value={property.rooms_count || 0} />
                        <Metric label="Desde" value={property.lowest_rate ? money(property.lowest_rate) : 'Sob consulta'} />
                    </div>
                </section>

                <section id="quartos" className="mx-auto max-w-7xl px-6 py-20">
                    <div className="mb-10 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">
                                Alojamento
                            </p>
                            <h2 className="mt-3 text-3xl font-bold">Quartos disponíveis</h2>
                        </div>
                        <p className="max-w-xl text-white/65">
                            Os preços são carregados a partir do módulo Quartos no AYA LodgeOS.
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {property.rooms.length > 0 ? (
                            property.rooms.map((room) => (
                                <article
                                    key={room.id}
                                    className="rounded-lg border border-white/10 bg-white/[0.06] p-6"
                                >
                                    <p className="text-sm text-white/55">
                                        {roomType[room.type] || room.type || 'Quarto'}
                                    </p>
                                    <h3 className="mt-2 text-2xl font-semibold">{room.name}</h3>
                                    <p className="mt-4 text-white/65">
                                        Capacidade para {room.capacity || 2} hóspede(s).
                                    </p>
                                    <div className="mt-6 flex items-end justify-between gap-4">
                                        <div>
                                            <p className="text-sm text-white/55">Preço por noite</p>
                                            <p className="text-2xl font-bold text-amber-300">
                                                {money(room.base_rate)}
                                            </p>
                                        </div>
                                        <a
                                            href={whatsapp}
                                            className="rounded-full border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-200 transition hover:bg-amber-300 hover:text-black"
                                        >
                                            Reservar
                                        </a>
                                    </div>
                                </article>
                            ))
                        ) : (
                            <div className="rounded-lg border border-white/10 bg-white/[0.06] p-6 text-white/70 md:col-span-2 lg:col-span-3">
                                Os quartos ainda não foram publicados para reservas directas.
                            </div>
                        )}
                    </div>
                </section>

                <section id="servicos" className="bg-white px-6 py-20 text-stone-950">
                    <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.9fr_1.1fr]">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">
                                Serviços
                            </p>
                            <h2 className="mt-3 text-3xl font-bold">O essencial para uma estadia sem fricção</h2>
                            <p className="mt-5 leading-7 text-stone-600">
                                A página pública usa a configuração do alojamento: políticas, depósito,
                                limpeza e serviços úteis ficam centralizados no painel.
                            </p>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Info label="Depósito de reserva" value={`${property.deposit_percent || 50}%`} />
                            <Info
                                label="Limpeza"
                                value={`A cada ${property.cleaning_interval_days || 3} dias`}
                            />
                            {(property.services.length > 0
                                ? property.services
                                : [
                                      { name: 'Pequeno-almoço', description: 'Disponível sob consulta' },
                                      { name: 'Transfer', description: 'Organização mediante pedido' },
                                  ]
                            ).map((service) => (
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
                        <a
                            href={whatsapp}
                            className="w-full rounded-full bg-amber-400 px-6 py-3 text-center font-semibold text-black transition hover:bg-amber-300 md:w-auto"
                        >
                            Falar pelo WhatsApp
                        </a>
                    </div>
                </section>
            </main>
        </>
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
