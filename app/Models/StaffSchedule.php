<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'staff_member_id',
        'schedule_month',
        'shift_date',
        'starts_at',
        'ends_at',
        'shift_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'schedule_month' => 'date',
        'shift_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (StaffSchedule $schedule) {
            $schedule->property_id = $schedule->property_id ?: ($schedule->staffMember?->property_id ?: TenantContext::propertyId());
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
