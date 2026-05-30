<?php

namespace App\Models;

use App\Support\TenantContext;
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
        static::saving(function (StockMovement $movement) {
            $movement->property_id = $movement->property_id ?: ($movement->stockItem?->property_id ?: TenantContext::propertyId());
        });

        static::created(function (StockMovement $movement) {
            self::applyQuantity($movement->stock_item_id, self::signedQuantity($movement->type, $movement->quantity));
        });

        static::updated(function (StockMovement $movement) {
            self::applyQuantity(
                $movement->getOriginal('stock_item_id'),
                -1 * self::signedQuantity($movement->getOriginal('type'), $movement->getOriginal('quantity')),
            );

            self::applyQuantity($movement->stock_item_id, self::signedQuantity($movement->type, $movement->quantity));
        });

        static::deleted(function (StockMovement $movement) {
            self::applyQuantity($movement->stock_item_id, -1 * self::signedQuantity($movement->type, $movement->quantity));
        });
    }

    private static function signedQuantity(?string $type, mixed $quantity): float
    {
        return ($type === 'out' ? -1 : 1) * (float) $quantity;
    }

    private static function applyQuantity(mixed $stockItemId, float $quantity): void
    {
        if (! $stockItemId || $quantity === 0.0) {
            return;
        }

        StockItem::query()->whereKey($stockItemId)->increment('quantity_on_hand', $quantity);
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
