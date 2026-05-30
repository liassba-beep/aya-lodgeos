<?php

namespace App\Models;

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

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}
