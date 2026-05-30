<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRequisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'stock_item_id',
        'staff_member_id',
        'quantity',
        'status',
        'needed_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'needed_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductRequisition $requisition) {
            $requisition->property_id = $requisition->property_id ?: TenantContext::propertyId();
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
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
