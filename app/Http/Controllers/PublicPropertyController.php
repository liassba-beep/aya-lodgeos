<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\TenantAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPropertyController extends Controller
{
    public function __invoke(Request $request, string $tenant): Response
    {
        $tenantAccount = TenantAccount::query()
            ->where('slug', $tenant)
            ->where('status', 'active')
            ->firstOrFail();

        $property = Property::query()
            ->with(['rooms' => fn ($query) => $query
                ->where('status', '!=', 'maintenance')
                ->orderBy('base_rate')
                ->orderBy('room_number')])
            ->where('tenant_account_id', $tenantAccount->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->firstOrFail();

        $services = collect($property->meals_and_services ?? [])
            ->map(fn ($value, $key): array => [
                'name' => is_string($key) ? $key : (string) $value,
                'description' => is_string($key) ? (string) $value : null,
            ])
            ->values();

        $rooms = $property->rooms
            ->map(fn ($room): array => [
                'id' => $room->id,
                'name' => $room->name,
                'type' => $room->type,
                'capacity' => $room->capacity,
                'base_rate' => (float) $room->base_rate,
                'status' => $room->status,
            ]);

        return Inertia::render('Public/Property', [
            'tenant' => [
                'name' => $tenantAccount->name,
                'slug' => $tenantAccount->slug,
            ],
            'property' => [
                'name' => $property->name,
                'legal_name' => $property->legal_name,
                'type' => $property->type,
                'city' => $property->city,
                'country' => $property->country,
                'address' => $property->address,
                'phone' => $property->phone ?: $property->invoice_phone,
                'email' => $property->email ?: $property->invoice_email,
                'deposit_percent' => (float) ($property->deposit_percent ?? 0),
                'cleaning_interval_days' => $property->cleaning_interval_days,
                'cancellation_policy' => $property->cancellation_policy,
                'house_rules' => $property->house_rules,
                'notes' => $property->notes,
                'lowest_rate' => $rooms->min('base_rate'),
                'rooms_count' => $rooms->count(),
                'rooms' => $rooms,
                'services' => $services,
            ],
        ]);
    }
}
