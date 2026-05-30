<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
