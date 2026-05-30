<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashClosure extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'staff_member_id', 'closure_date', 'opening_balance', 'cash_received', 'card_received', 'expenses_paid', 'expected_balance', 'counted_balance', 'difference', 'status', 'notes'];

    protected $casts = ['closure_date' => 'date'];

    protected static function booted(): void
    {
        static::saving(function (CashClosure $closure) {
            $closure->property_id = $closure->property_id ?: TenantContext::propertyId();
            $closure->expected_balance = (float) $closure->opening_balance + (float) $closure->cash_received + (float) $closure->card_received - (float) $closure->expenses_paid;
            $closure->difference = (float) $closure->counted_balance - (float) $closure->expected_balance;
        });
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}
