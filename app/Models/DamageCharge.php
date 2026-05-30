<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageCharge extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'reservation_id', 'room_id', 'description', 'amount', 'status', 'photo_path', 'notes'];

    protected $casts = ['amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(fn (DamageCharge $charge) => $charge->property_id = $charge->property_id ?: ($charge->reservation?->property_id ?: TenantContext::propertyId()));
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
