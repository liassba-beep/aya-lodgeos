<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
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

A MiKaya está comprometida com a preservação ambiental e conservação de recursos; agradecemos desde já o seu suporte. Normalmente, os lençóis e toalhas são trocados a cada 3 dias, a não ser que este procedimento seja solicitado antes.
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
                'country' => 'Mozambique',
                'address' => $city.', Mozambique',
                'deposit_percent' => 50,
                'cleaning_interval_days' => 3,
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

        $room = Room::query()->updateOrCreate(
            ['property_id' => $property->id, 'room_number' => '101'],
            [
                'name' => 'Quarto 101',
                'type' => 'double',
                'capacity' => 2,
                'base_rate' => 2800,
                'status' => 'available',
            ],
        );

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
}
