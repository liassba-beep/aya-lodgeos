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

        static::created(function (UtilityReading $reading) {
            OperationalAlert::create([
                'property_id' => $reading->property_id,
                'source_type' => UtilityReading::class,
                'source_id' => $reading->id,
                'severity' => 'info',
                'title' => 'Leitura Credelec registada',
                'message' => trim(collect([
                    $reading->meter_number ? 'Contador: '.$reading->meter_number : null,
                    $reading->balance_kwh !== null ? 'Saldo kWh: '.$reading->balance_kwh : null,
                    $reading->balance_amount !== null ? 'Saldo MZN: '.$reading->balance_amount : null,
                    $reading->staffMember?->name ? 'Registado por: '.$reading->staffMember->name : null,
                ])->filter()->implode("\n")),
                'status' => 'open',
            ]);
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
