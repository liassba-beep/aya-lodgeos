<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = ['payment_id', 'reservation_id', 'property_id', 'number', 'issued_at', 'amount', 'method', 'status', 'notes'];

    protected $casts = ['issued_at' => 'date', 'amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (Receipt $receipt) {
            $receipt->property_id = $receipt->property_id ?: ($receipt->reservation?->property_id ?: TenantContext::propertyId());
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
