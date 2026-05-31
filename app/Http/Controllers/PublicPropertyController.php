<?php

namespace App\Http\Controllers;

use App\Models\DirectBookingRequest;
use App\Models\Guest;
use App\Models\OperationalAlert;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\TenantAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicPropertyController extends Controller
{
    public function __invoke(Request $request, string $tenant): Response
    {
        return $this->show($request, $tenant);
    }

    public function show(Request $request, string $tenant): Response
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
            'booking' => [
                'availability_url' => $this->publicPath($request, $tenant, 'availability'),
                'store_url' => $this->publicPath($request, $tenant, 'booking-requests'),
            ],
        ]);
    }

    public function availability(Request $request, string $tenant): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $property = $this->propertyForTenant($tenant);
        $offer = $this->availableOffer($property, $validated['check_in'], $validated['check_out']);

        if (! $offer) {
            return response()->json([
                'available' => false,
                'message' => 'Não há quartos disponíveis para estas datas.',
            ]);
        }

        return response()->json([
            'available' => true,
            'room' => $offer['room'],
            'nights' => $offer['nights'],
            'nightly_rate' => $offer['nightly_rate'],
            'total' => $offer['total'],
        ]);
    }

    public function storeBookingRequest(Request $request, string $tenant): JsonResponse
    {
        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $property = $this->propertyForTenant($tenant);
        $offer = $this->availableOffer($property, $validated['check_in'], $validated['check_out']);

        if (! $offer) {
            throw ValidationException::withMessages([
                'check_in' => 'Não há quartos disponíveis para estas datas.',
            ]);
        }

        [$requestRecord, $reservation] = DB::transaction(function () use ($property, $validated, $offer): array {
            $message = trim(implode("\n", array_filter([
                $validated['message'] ?? null,
                'Quarto sugerido: '.$offer['room']['name'],
                'Noites: '.$offer['nights'],
                'Preço por noite: '.number_format($offer['nightly_rate'], 2, '.', ',').' MZN',
                'Total estimado: '.number_format($offer['total'], 2, '.', ',').' MZN',
            ])));

            $requestRecord = DirectBookingRequest::query()->create([
                'property_id' => $property->id,
                'guest_name' => $validated['guest_name'],
                'guest_phone' => $validated['guest_phone'],
                'guest_email' => $validated['guest_email'] ?? null,
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'adults' => $validated['adults'],
                'children' => $validated['children'],
                'status' => 'converted',
                'message' => $message,
            ]);

            $guest = $this->guestFromBookingRequest($property, $validated);

            $reservation = Reservation::query()->create([
                'property_id' => $property->id,
                'room_id' => $offer['room']['id'],
                'guest_id' => $guest->id,
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'adults' => $validated['adults'],
                'children' => $validated['children'],
                'breakfast_included' => true,
                'nightly_rate' => $offer['nightly_rate'],
                'discount_amount' => 0,
                'total_amount' => $offer['total'],
                'status' => 'pending',
                'source' => 'direct',
                'notes' => trim(implode("\n\n", array_filter([
                    'Pedido online #'.$requestRecord->id.' recebido pela página pública.',
                    $message,
                ]))),
            ]);

            OperationalAlert::query()->create([
                'property_id' => $property->id,
                'source_type' => Reservation::class,
                'source_id' => $reservation->id,
                'severity' => 'info',
                'title' => 'Nova reserva online para confirmar',
                'message' => $validated['guest_name'].' pediu '.$offer['room']['name'].' de '.$validated['check_in'].' a '.$validated['check_out'].'; total estimado '.number_format($offer['total'], 2, '.', ',').' MZN.',
                'status' => 'open',
            ]);

            return [$requestRecord, $reservation];
        });

        return response()->json([
            'ok' => true,
            'message' => 'Pedido recebido. A equipa da MiKaya vai confirmar a reserva consigo.',
            'reservation_code' => $reservation->code,
            'total' => $offer['total'],
        ], 201);
    }

    private function propertyForTenant(string $tenant): Property
    {
        $tenantAccount = TenantAccount::query()
            ->where('slug', $tenant)
            ->where('status', 'active')
            ->firstOrFail();

        return Property::query()
            ->where('tenant_account_id', $tenantAccount->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->firstOrFail();
    }

    private function availableOffer(Property $property, string $checkIn, string $checkOut): ?array
    {
        $start = Carbon::parse($checkIn)->toDateString();
        $end = Carbon::parse($checkOut)->toDateString();
        $nights = max(1, Carbon::parse($start)->diffInDays(Carbon::parse($end)));

        $room = Room::query()
            ->where('property_id', $property->id)
            ->where('status', '!=', 'maintenance')
            ->whereDoesntHave('reservations', function ($query) use ($start, $end) {
                $query
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('check_in', '<', $end)
                    ->whereDate('check_out', '>', $start);
            })
            ->orderBy('base_rate')
            ->orderBy('room_number')
            ->first();

        if (! $room) {
            return null;
        }

        $nightlyRate = (float) $room->base_rate;

        return [
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
            ],
            'nights' => $nights,
            'nightly_rate' => $nightlyRate,
            'total' => $nightlyRate * $nights,
        ];
    }

    private function guestFromBookingRequest(Property $property, array $validated): Guest
    {
        $name = trim($validated['guest_name']);
        $parts = preg_split('/\s+/', $name, 2) ?: [$name];

        $guest = Guest::query()
            ->where('property_id', $property->id)
            ->where(function ($query) use ($validated) {
                $query->where('phone', $validated['guest_phone']);

                if (filled($validated['guest_email'] ?? null)) {
                    $query->orWhere('email', $validated['guest_email']);
                }
            })
            ->first() ?? new Guest(['property_id' => $property->id]);

        $guest->forceFill([
            'first_name' => $parts[0] ?: $name,
            'last_name' => $parts[1] ?? '-',
            'phone' => $validated['guest_phone'],
            'email' => $validated['guest_email'] ?? $guest->email,
            'country' => $guest->country ?: 'Mozambique',
        ])->save();

        return $guest;
    }

    private function publicPath(Request $request, string $tenant, string $path): string
    {
        return $request->routeIs('public.property.preview')
            ? "/p/{$tenant}/{$path}"
            : "/{$path}";
    }
}
