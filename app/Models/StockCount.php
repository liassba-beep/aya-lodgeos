<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCount extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'stock_item_id', 'staff_member_id', 'count_date', 'system_quantity', 'counted_quantity', 'difference', 'status', 'notes'];

    protected $casts = ['count_date' => 'date', 'system_quantity' => 'decimal:2', 'counted_quantity' => 'decimal:2', 'difference' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (StockCount $count) {
            $count->property_id = $count->property_id ?: ($count->stockItem?->property_id ?: TenantContext::propertyId());
            $count->system_quantity = $count->system_quantity ?: ($count->stockItem?->quantity_on_hand ?? 0);
            $count->difference = (float) $count->counted_quantity - (float) $count->system_quantity;
        });
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}
