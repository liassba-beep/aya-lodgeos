<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_item_id',
        'property_id',
        'type',
        'quantity',
        'unit_cost',
        'movement_date',
        'reason',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'movement_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (StockMovement $movement) {
            $item = $movement->stockItem;

            if (! $item) {
                return;
            }

            $factor = $movement->type === 'out' ? -1 : 1;
            $item->increment('quantity_on_hand', $factor * (float) $movement->quantity);
        });
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
