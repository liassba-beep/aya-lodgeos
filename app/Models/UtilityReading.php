<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'staff_member_id',
        'reading_date',
        'meter_name',
        'meter_number',
        'balance_kwh',
        'balance_amount',
        'qr_code',
        'photo_path',
        'notes',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'balance_kwh' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (UtilityReading $reading) {
            $reading->property_id = $reading->property_id ?: TenantContext::propertyId();
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}
