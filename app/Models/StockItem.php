<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'category',
        'unit',
        'unit_cost',
        'quantity_on_hand',
        'minimum_quantity',
        'location',
        'status',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'quantity_on_hand' => 'decimal:2',
        'minimum_quantity' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (StockItem $stockItem) {
            $stockItem->property_id = $stockItem->property_id ?: TenantContext::propertyId();
        });

        static::saved(function (StockItem $stockItem) {
            if ((float) $stockItem->minimum_quantity <= 0) {
                return;
            }

            if ((float) $stockItem->quantity_on_hand > (float) $stockItem->minimum_quantity) {
                return;
            }

            OperationalAlert::updateOrCreate(
                [
                    'property_id' => $stockItem->property_id,
                    'source_type' => StockItem::class,
                    'source_id' => $stockItem->id,
                    'status' => 'open',
                ],
                [
                    'severity' => 'warning',
                    'title' => 'Stock baixo: '.$stockItem->name,
                    'message' => sprintf(
                        'Quantidade actual: %s %s. Mínimo definido: %s %s.',
                        $stockItem->quantity_on_hand,
                        $stockItem->unit,
                        $stockItem->minimum_quantity,
                        $stockItem->unit,
                    ),
                ],
            );
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
