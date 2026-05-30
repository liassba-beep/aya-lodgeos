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
