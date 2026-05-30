<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
        'nightly_rate',
        'total_amount',
        'status',
        'source',
        'notes',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
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
