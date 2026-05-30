<?php

namespace App\Support;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationAvailability
{
    public static function assertAvailable(Reservation $reservation): void
    {
        if (! $reservation->room_id || ! $reservation->check_in || ! $reservation->check_out || $reservation->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($reservation): void {
            Room::query()->whereKey($reservation->room_id)->lockForUpdate()->first();

            $conflict = Reservation::query()
                ->where('room_id', $reservation->room_id)
                ->where('status', '!=', 'cancelled')
                ->when($reservation->exists, fn ($query) => $query->whereKeyNot($reservation->getKey()))
                ->whereDate('check_in', '<', $reservation->check_out)
                ->whereDate('check_out', '>', $reservation->check_in)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'room_id' => 'Este quarto ja tem uma reserva ativa para estas datas.',
                ]);
            }
        });
    }
}
