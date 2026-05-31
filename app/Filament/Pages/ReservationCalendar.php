<?php

namespace App\Filament\Pages;

use App\Models\Reservation;
use App\Models\Room;
use App\Support\AccessControl;
use App\Support\TenantContext;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class ReservationCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Reservas';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Calendário';

    protected static ?string $title = 'Calendário de reservas';

    protected static string $view = 'filament.pages.reservation-calendar';

    public static function canAccess(): bool
    {
        return AccessControl::allows('reservation', 'view') || AccessControl::allows('*', 'view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && AccessControl::shouldRegisterNavigation('reservation');
    }

    public static function getNavigationGroup(): ?string
    {
        return AccessControl::navigationGroup('reservation');
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public function calendarData(): array
    {
        try {
            $requestedMonth = (string) request()->query('month', now()->format('Y-m'));
            $month = preg_match('/^\d{4}-\d{2}$/', $requestedMonth)
                ? Carbon::createFromFormat('Y-m-d', $requestedMonth.'-01')->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }
        $start = $month->copy();
        $end = $month->copy()->endOfMonth();
        $propertyId = TenantContext::propertyId();

        $rooms = Room::query()
            ->when($propertyId, fn ($query, int $propertyId) => $query->where('property_id', $propertyId))
            ->orderBy('room_number')
            ->orderBy('name')
            ->get();

        $reservations = Reservation::query()
            ->with(['guest', 'room'])
            ->when($propertyId, fn ($query, int $propertyId) => $query->where('property_id', $propertyId))
            ->where('status', '!=', 'cancelled')
            ->whereDate('check_in', '<=', $end)
            ->whereDate('check_out', '>=', $start)
            ->get()
            ->groupBy('room_id');

        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days[] = [
                'key' => $date->format('Y-m-d'),
                'label' => $date->format('d'),
                'weekday' => $date->isoFormat('dd'),
            ];
        }

        return [
            'title' => $month->isoFormat('MMMM YYYY'),
            'previous' => $month->copy()->subMonth()->format('Y-m'),
            'next' => $month->copy()->addMonth()->format('Y-m'),
            'days' => $days,
            'rooms' => $rooms->map(fn (Room $room): array => [
                'id' => $room->id,
                'name' => $room->name,
                'number' => $room->room_number,
                'reservations' => ($reservations[$room->id] ?? collect())
                    ->filter(fn (Reservation $reservation): bool => filled($reservation->check_in) && filled($reservation->check_out))
                    ->map(fn (Reservation $reservation): array => [
                        'id' => $reservation->id,
                        'guest' => $reservation->guest?->full_name,
                        'code' => $reservation->code,
                        'check_in' => $reservation->check_in?->format('Y-m-d'),
                        'check_out' => $reservation->check_out?->format('Y-m-d'),
                        'nights' => $reservation->check_in && $reservation->check_out
                            ? max(1, (int) $reservation->check_in->diffInDays($reservation->check_out))
                            : 1,
                        'status' => $reservation->status,
                    ])->values()->all(),
            ])->all(),
        ];
    }
}
