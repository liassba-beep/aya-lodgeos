<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomInventory extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'room_id', 'item_name', 'expected_quantity', 'current_quantity', 'replacement_cost', 'status', 'notes'];

    protected $casts = ['expected_quantity' => 'decimal:2', 'current_quantity' => 'decimal:2', 'replacement_cost' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(fn (RoomInventory $inventory) => $inventory->property_id = $inventory->property_id ?: ($inventory->room?->property_id ?: TenantContext::propertyId()));
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
