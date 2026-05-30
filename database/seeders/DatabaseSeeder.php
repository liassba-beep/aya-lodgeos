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
