<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'reservation_id',
        'number',
        'issued_at',
        'due_at',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'tax_rate',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice) {
            $invoice->property_id = $invoice->property_id ?: ($invoice->reservation?->property_id ?: TenantContext::propertyId());

            $invoice->total_amount = max(
                0,
                (float) $invoice->subtotal - (float) $invoice->discount_amount + (float) $invoice->tax_amount,
            );
        });

        static::saved(function (Invoice $invoice) {
            $invoice->syncPaymentStatus();
        });
    }

    public function getPaidAmountAttribute(): float
    {
        if (! $this->reservation_id) {
            return 0;
        }

        return (float) Payment::query()
            ->where('reservation_id', $this->reservation_id)
            ->where('status', 'paid')
            ->sum('amount');
    }

    public function getBalanceAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->paid_amount);
    }

    public function syncPaymentStatus(): void
    {
        if ($this->status === 'cancelled' || ! $this->exists) {
            return;
        }

        $nextStatus = $this->paid_amount >= (float) $this->total_amount && (float) $this->total_amount > 0
            ? 'paid'
            : ($this->status === 'paid' ? 'issued' : $this->status);

        if ($nextStatus !== $this->status) {
            $this->forceFill(['status' => $nextStatus])->saveQuietly();
        }
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
