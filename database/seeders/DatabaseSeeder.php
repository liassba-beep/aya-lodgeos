<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TenantAccount;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const MIKAYA_CANCELLATION_POLICY = <<<'TEXT'
RESERVAS E PAGAMENTOS

Registo: Todos os hóspedes devem fornecer identificação válida emitida pelo governo, por exemplo passaporte ou ID, e registar os seus dados de contacto na chegada.

Pagamentos: O pagamento completo ou um depósito, normalmente 50%, é necessário para garantir a reserva.

Cancelamentos: O reembolso pós-cancelamento seguirá a percentagem abaixo:
7 dias: 75% do valor da reserva.
3 dias: 35% do valor da reserva.
24h: sem reembolso.

Check-in inicia às 12:00 horas. Ajustes podem ser efectuados caso a reserva tenha sido combinada antecipadamente.

Check-out deverá ser efectuado até às 10:00 horas.
TEXT;

    private const MIKAYA_HOUSE_RULES = <<<'TEXT'
ACOMODAÇÃO

Não é permitido cozinhar ou comer nos quartos, sendo o uso da cozinha comum regulamentado.

Fumar e danos: Fumar é estritamente proibido no interior dos quartos, com multas por violações. Os hóspedes são responsáveis por qualquer dano ou perda de propriedade. É expressamente proibida a confecção de refeições no interior dos quartos ou em qualquer outra área da MiKaya.

Festas: Festas e eventos não são autorizados. O nosso ambiente é de paz e tranquilidade. Apelamos para que cada hóspede seja consciente e respeite os vizinhos, para que todos possam ter uma estadia agradável, relaxante e sem barulho.

Animais de estimação: Os hóspedes não podem trazer animais de estimação de qualquer tipo para a guest house.

SERVIÇOS E LIMPEZA

A MiKaya está comprometida com a preservação ambiental e conservação de recursos; agradecemos desde já o seu suporte. A limpeza é diária. A troca de lençóis e toalhas pode ser ajustada conforme a necessidade da estadia.
TEXT;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedTenant(
            slug: 'mikaya',
            tenantName: 'MiKaya Guest House',
            propertyName: 'MiKaya',
            city: 'Inhambane',
            ownerEmail: 'proprietario@mikaya.lodgesos.com',
            reservationCode: 'RSV-SEED-MIKAYA',
        );

        $this->seedTenant(
            slug: 'demo',
            tenantName: 'LodgeOS Demo',
            propertyName: 'Demo Lodge',
            city: 'Maputo',
            ownerEmail: 'proprietario@demo.lodgesos.com',
            reservationCode: 'RSV-SEED-DEMO',
        );
    }

    private function seedTenant(
        string $slug,
        string $tenantName,
        string $propertyName,
        string $city,
        string $ownerEmail,
        string $reservationCode,
    ): void {
        $tenant = TenantAccount::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $tenantName,
                'status' => 'active',
                'billing_email' => $ownerEmail,
                'billing_phone' => $slug === 'mikaya' ? '+258 84 299 0406' : '+258 84 000 0000',
                'whatsapp_number' => $slug === 'mikaya' ? '258842990406' : '258840000000',
                'latitude' => $slug === 'mikaya' ? -23.8803125 : -25.9662000,
                'longitude' => $slug === 'mikaya' ? 35.4183125 : 32.5832000,
                'address_label' => $slug === 'mikaya' ? '4C99+V8V, Inhambane' : $city.', Moçambique',
                'directions_note' => $slug === 'mikaya'
                    ? 'Use o código 4C99+V8V no Google Maps ou peça a localização exacta no WhatsApp antes da chegada. A equipa confirma o melhor trajecto conforme o ponto de partida.'
                    : 'Substitua esta nota pela orientação real de chegada do alojamento.',
                'nearby_json' => $slug === 'mikaya' ? [
                    'Praia do Tofo' => 'aprox. 25 minutos',
                    'Praia da Barra' => 'aprox. 30 minutos',
                    'Aeroporto de Inhambane' => 'aprox. 10 minutos',
                    'Centro de Inhambane' => 'aprox. 15 minutos',
                ] : [
                    'Centro da cidade' => 'a confirmar',
                    'Aeroporto' => 'a confirmar',
                ],
                'seo_title' => $slug === 'mikaya'
                    ? 'MiKaya Guest House - Inhambane | Reservas directas'
                    : $propertyName.' - '.$city.' | Reservas directas',
                'seo_description' => $slug === 'mikaya'
                    ? 'Reserve directamente na MiKaya Guest House em Inhambane: quartos com WC privativo e kitnet, café da manhã e acesso rápido às praias do Tofo e Barra.'
                    : 'Reserve directamente em '.$propertyName.', '.$city.'. Edite esta descrição SEO no painel do proprietário.',
                'og_image' => $slug === 'mikaya' ? '/images/mikaya-hero.jpg' : '/images/mikaya-showcase.jpg',
            ],
        );

        $property = Property::query()->updateOrCreate(
            ['name' => $propertyName],
            [
                'tenant_account_id' => $tenant->id,
                'type' => 'guest_house',
                'status' => 'active',
                'email' => $ownerEmail,
                'city' => $city,
                'country' => 'Moçambique',
                'address' => $city.', Moçambique',
                'deposit_percent' => 50,
                'cleaning_interval_days' => $slug === 'mikaya' ? 1 : 3,
                'cancellation_policy' => $slug === 'mikaya' ? self::MIKAYA_CANCELLATION_POLICY : null,
                'house_rules' => $slug === 'mikaya' ? self::MIKAYA_HOUSE_RULES : null,
                'meals_and_services' => $slug === 'mikaya' ? [
                    'Café da manhã' => 'Disponível para começar o dia com calma.',
                    '4 quartos' => 'Com casa de banho privativa e kitnet.',
                    'DSTV' => 'Entretenimento disponível nos quartos.',
                    'Starlink Wi-Fi gratuito' => 'Internet estável para hóspedes.',
                    'Ar condicionado' => 'Conforto térmico durante a estadia.',
                    'Geleira' => 'Apoio prático para estadias curtas ou prolongadas.',
                    'CCTV' => 'Câmaras nas áreas públicas.',
                    'Localização estratégica' => 'A poucos minutos do centro da cidade e das praias do Tofo e Barra.',
                    'Idiomas' => 'Português.',
                ] : null,
            ],
        );

        $owner = User::query()->updateOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => 'Proprietário '.$propertyName,
                'property_id' => $property->id,
                'role' => 'owner',
                'password' => Hash::make('AyaLodgeOS#2026'),
                'locale' => 'pt_PT',
                'theme_mode' => 'system',
            ],
        );

        $owner->properties()->syncWithoutDetaching([
            $property->id => ['role' => 'owner', 'permissions' => null],
        ]);

        $room = null;

        foreach (range(1, $slug === 'mikaya' ? 4 : 2) as $index) {
            $room = Room::query()->updateOrCreate(
                ['property_id' => $property->id, 'room_number' => '10'.$index],
                [
                    'name' => 'Quarto 10'.$index,
                    'type' => 'double',
                    'capacity' => 2,
                    'base_rate' => 2800,
                    'status' => 'available',
                ],
            );
        }

        $this->seedWebsiteContent($tenant, $slug, $propertyName);

        $guest = Guest::query()->updateOrCreate(
            ['property_id' => $property->id, 'email' => 'hospede.'.$slug.'@example.com'],
            [
                'first_name' => 'Hóspede',
                'last_name' => ucfirst($slug),
                'phone' => '+258840000001',
                'country' => 'Mozambique',
            ],
        );

        Reservation::query()->updateOrCreate(
            ['code' => $reservationCode],
            [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'check_in' => now()->addDays($slug === 'mikaya' ? 7 : 14)->toDateString(),
                'check_out' => now()->addDays($slug === 'mikaya' ? 9 : 16)->toDateString(),
                'adults' => 1,
                'children' => 0,
                'nightly_rate' => $room->base_rate,
                'total_amount' => 5600,
                'status' => 'confirmed',
                'source' => 'direct',
            ],
        );
    }

    private function seedWebsiteContent(TenantAccount $tenant, string $slug, string $propertyName): void
    {
        $photoPath = $slug === 'mikaya' ? '/images/mikaya-hero.jpg' : '/images/mikaya-showcase.jpg';
        $showcasePath = $slug === 'mikaya' ? '/images/mikaya-showcase.jpg' : '/images/mikaya-hero.jpg';

        $photos = $slug === 'mikaya' ? [
            ['Quarto 1 com WC privativo e kitnet - placeholder a substituir', 'quarto', $photoPath],
            ['Quarto 2 preparado para estadia tranquila - placeholder a substituir', 'quarto', $showcasePath],
            ['Quarto 3 com comodidades essenciais - placeholder a substituir', 'quarto', $photoPath],
            ['Quarto 4 para reserva directa - placeholder a substituir', 'quarto', $showcasePath],
            ['Casa de banho e kitnet do alojamento - placeholder a substituir', 'kitnet', $photoPath],
            ['Café da manhã da guest house - placeholder a substituir', 'refeicoes', $showcasePath],
            ['Envolvente com acesso às praias do Tofo e Barra - placeholder a substituir', 'envolvente', $photoPath],
        ] : [
            ['Foto principal do alojamento demo - substituir no painel', 'exterior', $photoPath],
            ['Quarto demo - substituir no painel', 'quarto', $showcasePath],
            ['Envolvente demo - substituir no painel', 'envolvente', $photoPath],
        ];

        foreach ($photos as $index => [$alt, $category, $path]) {
            PropertyPhoto::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'alt' => $alt],
                [
                    'path' => $path,
                    'caption' => 'Placeholder a substituir pelo proprietário.',
                    'category' => $category,
                    'sort_order' => $index + 1,
                ],
            );
        }

        $roomTypes = $slug === 'mikaya' ? [
            'Quarto 1',
            'Quarto 2',
            'Quarto 3',
            'Quarto 4',
        ] : [
            'Quarto Standard',
            'Quarto Familiar',
        ];

        foreach ($roomTypes as $index => $name) {
            RoomType::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                [
                    'description' => $slug === 'mikaya'
                        ? 'Quarto com WC privativo, kitnet e comodidades essenciais para estadias em Inhambane.'
                        : 'Descrição demo configurável no painel do proprietário.',
                    'capacity' => $index === 1 && $slug !== 'mikaya' ? 4 : 2,
                    'price_from' => 2800,
                    'amenities_json' => $slug === 'mikaya'
                        ? ['WC privativo', 'Kitnet', 'Café da manhã', 'Limpeza diária', 'Depósito de 50%']
                        : ['Edite os benefícios no painel'],
                    'photo' => $index % 2 === 0 ? $photoPath : $showcasePath,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
