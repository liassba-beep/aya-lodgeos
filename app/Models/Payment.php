<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'amount',
        'method',
        'status',
        'paid_at',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Payment $payment) {
            if ((! $payment->amount || (float) $payment->amount <= 0) && $payment->reservation) {
                $payment->amount = $payment->reservation->total_amount;
            }

            if ($payment->status === 'paid' && ! $payment->paid_at) {
                $payment->paid_at = now();
            }
        });

        static::saved(function (Payment $payment) {
            $payment->reservation?->invoices->each(fn (Invoice $invoice) => $invoice->syncPaymentStatus());
        });

        static::deleted(function (Payment $payment) {
            $payment->reservation?->invoices->each(fn (Invoice $invoice) => $invoice->syncPaymentStatus());
        });
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function receipt(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
