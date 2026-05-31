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
use Illuminate\Support\Facades\Storage;
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
        Inertia::setRootView('public');

        $tenantAccount = TenantAccount::query()
            ->with([
                'photos' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'roomTypes' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'testimonials' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
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

        $photos = $tenantAccount->photos
            ->map(function ($photo): array {
                $src = $this->publicAsset($photo->path);

                return [
                    'id' => $photo->id,
                    'src' => $src,
                    'srcset' => $this->imageSrcset($src),
                    'alt' => $photo->alt,
                    'caption' => $photo->caption,
                    'category' => $photo->category,
                ];
            })
            ->values();

        $roomTypes = $tenantAccount->roomTypes
            ->map(function ($roomType): array {
                $photo = $this->publicAsset($roomType->photo);

                return [
                    'id' => $roomType->id,
                    'name' => $roomType->name,
                    'description' => $roomType->description,
                    'capacity' => $roomType->capacity,
                    'price_from' => (float) $roomType->price_from,
                    'amenities' => $roomType->amenities_json ?? [],
                    'photo' => $photo,
                    'srcset' => $this->imageSrcset($photo),
                ];
            })
            ->values();

        if ($roomTypes->isEmpty()) {
            $roomTypes = $rooms
                ->map(fn (array $room): array => [
                    'id' => $room['id'],
                    'name' => $room['name'],
                    'description' => null,
                    'capacity' => $room['capacity'],
                    'price_from' => $room['base_rate'],
                    'amenities' => [],
                    'photo' => null,
                ])
                ->values();
        }

        $nearby = collect($tenantAccount->nearby_json ?? [])
            ->map(fn ($distance, $name): array => ['name' => (string) $name, 'distance' => (string) $distance])
            ->values();

        $firstPhoto = $photos->first();
        $heroImage = $this->publicAsset($tenantAccount->og_image) ?: ($firstPhoto['src'] ?? null);
        $seoTitle = $tenantAccount->seo_title ?: trim(($tenantAccount->name ?: $property->name).' - '.($property->city ?: 'Moçambique').' | Reservas directas');
        $seoDescription = $tenantAccount->seo_description ?: 'Reserve directamente em '.$property->name.'. Consulte disponibilidade, contactos, localização e quartos disponíveis.';
        $canonicalUrl = $request->url();

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
            'website' => [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'canonical_url' => $canonicalUrl,
                'og_image' => $this->absoluteUrl($request, $heroImage),
                'favicon' => $this->absoluteUrl($request, $this->publicAsset($tenantAccount->favicon_path)),
                'hero_image' => $heroImage,
                'hero_srcset' => $this->imageSrcset($heroImage),
                'whatsapp_number' => $tenantAccount->whatsapp_number,
                'whatsapp_url' => $this->whatsappUrl($tenantAccount->whatsapp_number, 'Olá, quero reservar em '.$property->name.'. Datas: __ a __.'),
                'latitude' => $tenantAccount->latitude ? (float) $tenantAccount->latitude : null,
                'longitude' => $tenantAccount->longitude ? (float) $tenantAccount->longitude : null,
                'address_label' => $tenantAccount->address_label,
                'directions_note' => $tenantAccount->directions_note,
                'nearby' => $nearby,
                'photos' => $photos,
                'room_types' => $roomTypes,
                'testimonials' => $tenantAccount->testimonials
                    ->map(fn ($testimonial): array => [
                        'id' => $testimonial->id,
                        'author' => $testimonial->author,
                        'text' => $testimonial->text,
                        'rating' => $testimonial->rating,
                        'source' => $testimonial->source,
                    ])
                    ->values(),
                'json_ld' => $this->lodgingBusinessSchema($request, $tenantAccount, $property, $heroImage, $roomTypes, $services),
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
        $property->load('tenantAccount');
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
        $property->load('tenantAccount');
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
            'message' => 'Recebemos o seu pedido. Confirmamos por WhatsApp ou email em breve. A reserva só fica garantida após o depósito de '.(float) ($property->deposit_percent ?? 50).'%.',
            'reservation_code' => $reservation->code,
            'total' => $offer['total'],
            'whatsapp_url' => $this->whatsappUrl(
                $property->tenantAccount?->whatsapp_number,
                trim(implode("\n", [
                    'Olá, enviei um pedido de reserva em '.$property->name.'.',
                    'Nome: '.$validated['guest_name'],
                    'Telefone: '.$validated['guest_phone'],
                    'Datas: '.$validated['check_in'].' a '.$validated['check_out'],
                    'Adultos: '.$validated['adults'].'; Crianças: '.$validated['children'],
                    'Reserva: '.$reservation->code,
                    'Total estimado: '.number_format($offer['total'], 2, '.', ',').' MZN',
                ])),
            ),
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

    private function publicAsset(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::url($path);
    }

    private function absoluteUrl(Request $request, ?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim($request->getSchemeAndHttpHost(), '/').'/'.ltrim($path, '/');
    }

    private function whatsappUrl(?string $number, string $message): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if (! $digits) {
            return null;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    private function imageSrcset(?string $image): ?array
    {
        if (! $image) {
            return null;
        }

        $withoutExtension = preg_replace('/\.(jpe?g|png|webp|avif)$/i', '', $image);

        if (! $withoutExtension) {
            return null;
        }

        $srcset = ['fallback' => $image];

        foreach (['avif', 'webp'] as $format) {
            $candidate = $withoutExtension.'.'.$format;

            if (str_starts_with($candidate, '/') && file_exists(public_path(ltrim($candidate, '/')))) {
                $srcset[$format] = $candidate.' 1200w';
            }
        }

        return $srcset;
    }

    private function lodgingBusinessSchema(Request $request, TenantAccount $tenant, Property $property, ?string $image, $roomTypes, $services): array
    {
        $amenities = $services
            ->pluck('name')
            ->merge($roomTypes->flatMap(fn (array $room): array => $room['amenities'] ?? []))
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $amenity): array => [
                '@type' => 'LocationFeatureSpecification',
                'name' => $amenity,
                'value' => true,
            ]);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LodgingBusiness',
            'name' => $tenant->name,
            'url' => $request->url(),
            'image' => $this->absoluteUrl($request, $image),
            'telephone' => $property->phone ?: $property->invoice_phone ?: $tenant->billing_phone,
            'priceRange' => $roomTypes->min('price_from') ? 'Desde '.number_format((float) $roomTypes->min('price_from'), 0, '.', ' ').' MZN' : null,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $tenant->address_label ?: $property->address,
                'addressLocality' => $property->city,
                'addressCountry' => $property->country ?: 'Mozambique',
            ],
            'geo' => $tenant->latitude && $tenant->longitude ? [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $tenant->latitude,
                'longitude' => (float) $tenant->longitude,
            ] : null,
            'amenityFeature' => $amenities->all(),
        ]);
    }
}
