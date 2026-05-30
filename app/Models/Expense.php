<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'category',
        'supplier',
        'amount',
        'expense_date',
        'payment_method',
        'status',
        'reference',
        'stock_item_id',
        'stock_quantity',
        'stock_unit_cost',
        'stock_movement_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'stock_quantity' => 'decimal:2',
        'stock_unit_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (Expense $expense) {
            $expense->syncStockMovement();
        });

        static::deleted(function (Expense $expense) {
            $expense->stockMovement?->delete();
        });
    }

    public function syncStockMovement(): void
    {
        if ($this->category !== 'stock' || ! $this->stock_item_id || (float) $this->stock_quantity <= 0) {
            if ($this->stockMovement) {
                $this->stockMovement->delete();
                $this->forceFill(['stock_movement_id' => null])->saveQuietly();
            }

            return;
        }

        $data = [
            'stock_item_id' => $this->stock_item_id,
            'property_id' => $this->property_id,
            'type' => 'in',
            'quantity' => $this->stock_quantity,
            'unit_cost' => $this->stock_unit_cost ?: ((float) $this->amount / max(1, (float) $this->stock_quantity)),
            'movement_date' => $this->expense_date,
            'reason' => trim('Despesa '.$this->reference),
            'notes' => $this->notes,
        ];

        if ($this->stock_movement_id && $this->stockMovement) {
            $movement = $this->stockMovement;
            $movement->update($data);
        } else {
            $movement = StockMovement::create($data);
        }

        if ($movement->id !== $this->stock_movement_id) {
            $this->forceFill(['stock_movement_id' => $movement->id])->saveQuietly();
        }
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }
}
