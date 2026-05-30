<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'description', 'quantity', 'unit_price', 'tax_rate', 'line_total'];

    protected $casts = ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'tax_rate' => 'decimal:2', 'line_total' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (InvoiceLine $line) {
            $line->line_total = (float) $line->quantity * (float) $line->unit_price;
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
