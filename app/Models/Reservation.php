<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_id',
        'guest_id',
        'code',
        'check_in',
        'check_out',
        'adults',
        'children',
        'breakfast_included',
        'nightly_rate',
        'total_amount',
        'status',
        'source',
        'notes',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'breakfast_included' => 'boolean',
        'nightly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            if (! $reservation->code) {
                $reservation->code = 'RSV-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            }
        });

        static::saving(function (Reservation $reservation) {
            $room = $reservation->room;

            if ($room) {
                $reservation->property_id = $room->property_id;

                if (! $reservation->nightly_rate || (float) $reservation->nightly_rate <= 0) {
                    $reservation->nightly_rate = $room->base_rate;
                }
            }

            $reservation->total_amount = static::calculateTotal(
                $reservation->check_in,
                $reservation->check_out,
                $reservation->nightly_rate,
            );

            if ($reservation->hasDateConflict()) {
                throw ValidationException::withMessages([
                    'room_id' => 'Este quarto ja tem uma reserva ativa para estas datas.',
                ]);
            }
        });
    }

    public static function calculateTotal(mixed $checkIn, mixed $checkOut, mixed $nightlyRate): float
    {
        if (! $checkIn || ! $checkOut || ! $nightlyRate) {
            return 0;
        }

        $nights = Carbon::parse($checkIn)->startOfDay()
            ->diffInDays(Carbon::parse($checkOut)->startOfDay(), false);

        return max(0, $nights) * (float) $nightlyRate;
    }

    public function hasDateConflict(): bool
    {
        if (! $this->room_id || ! $this->check_in || ! $this->check_out || $this->status === 'cancelled') {
            return false;
        }

        return static::query()
            ->where('room_id', $this->room_id)
            ->where('status', '!=', 'cancelled')
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->whereDate('check_in', '<', $this->check_out)
            ->whereDate('check_out', '>', $this->check_in)
            ->exists();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
