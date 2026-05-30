<?php

namespace Tests\Feature;

use App\Filament\Resources\ReservationResource;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\TenantAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_open_another_property_reservation_in_filament(): void
    {
        [$propertyA, $ownerA] = $this->tenantFixture('mikaya');
        [$propertyB] = $this->tenantFixture('demo');
        $reservationB = $this->reservationFor($propertyB, 'RSV-ISO-DEMO');

        $this->actingAs($ownerA)
            ->get(ReservationResource::getUrl('edit', ['record' => $reservationB]))
            ->assertNotFound();
    }

    public function test_filament_reservation_query_is_scoped_to_authenticated_property(): void
    {
        [$propertyA, $ownerA] = $this->tenantFixture('mikaya');
        [$propertyB] = $this->tenantFixture('demo');

        $reservationA = $this->reservationFor($propertyA, 'RSV-ISO-MIKAYA');
        $reservationB = $this->reservationFor($propertyB, 'RSV-ISO-DEMO');

        $this->actingAs($ownerA);

        $this->assertTrue(
            ReservationResource::getEloquentQuery()->whereKey($reservationA->id)->exists(),
            'O proprietário deve ver reservas do próprio alojamento.',
        );

        $this->assertFalse(
            ReservationResource::getEloquentQuery()->whereKey($reservationB->id)->exists(),
            'O proprietário não pode ver reservas de outro alojamento.',
        );
    }

    private function tenantFixture(string $slug): array
    {
        $tenant = TenantAccount::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'active',
        ]);

        $property = Property::query()->create([
            'tenant_account_id' => $tenant->id,
            'name' => ucfirst($slug).' Lodge',
            'type' => 'guest_house',
            'status' => 'active',
            'city' => 'Inhambane',
            'country' => 'Mozambique',
        ]);

        $owner = User::query()->create([
            'name' => 'Proprietário '.ucfirst($slug),
            'email' => $slug.'@example.com',
            'password' => 'password',
            'property_id' => $property->id,
            'role' => 'owner',
        ]);

        $owner->properties()->syncWithoutDetaching([
            $property->id => ['role' => 'owner', 'permissions' => null],
        ]);

        return [$property, $owner];
    }

    private function reservationFor(Property $property, string $code): Reservation
    {
        $room = Room::query()->create([
            'property_id' => $property->id,
            'name' => 'Quarto 1',
            'room_number' => '1',
            'type' => 'double',
            'capacity' => 2,
            'base_rate' => 2800,
            'status' => 'available',
        ]);

        $guest = Guest::query()->create([
            'property_id' => $property->id,
            'first_name' => 'Hóspede',
            'last_name' => $property->name,
            'email' => strtolower(str($code)->slug()).'@example.com',
            'country' => 'Mozambique',
        ]);

        return Reservation::query()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'code' => $code,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'adults' => 1,
            'children' => 0,
            'nightly_rate' => 2800,
            'total_amount' => 5600,
            'status' => 'confirmed',
            'source' => 'direct',
        ]);
    }
}
