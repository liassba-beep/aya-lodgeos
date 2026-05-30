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

    protected static ?string $navigationLabel = 'Calendario';

    protected static ?string $title = 'Calendario de reservas';

    protected static string $view = 'filament.pages.reservation-calendar';

    public static function canAccess(): bool
    {
        return AccessControl::allows('reservation', 'view') || AccessControl::allows('*', 'view');
    }

    public function calendarData(): array
    {
        $month = Carbon::createFromFormat('Y-m', request()->query('month', now()->format('Y-m')))->startOfMonth();
        $start = $month->copy();
        $end = $month->copy()->endOfMonth();
        $propertyId = TenantContext::propertyId();

        $rooms = Room::query()
            ->where('property_id', $propertyId)
            ->orderBy('room_number')
            ->orderBy('name')
            ->get();

        $reservations = Reservation::query()
            ->with(['guest', 'room'])
            ->where('property_id', $propertyId)
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
                'reservations' => ($reservations[$room->id] ?? collect())->map(fn (Reservation $reservation): array => [
                    'guest' => $reservation->guest?->full_name,
                    'code' => $reservation->code,
                    'check_in' => $reservation->check_in?->format('Y-m-d'),
                    'check_out' => $reservation->check_out?->format('Y-m-d'),
                    'status' => $reservation->status,
                ])->values()->all(),
            ])->all(),
        ];
    }
}
