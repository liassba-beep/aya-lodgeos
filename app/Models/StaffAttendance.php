<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'staff_member_id', 'attendance_date', 'checked_in_at', 'checked_out_at', 'checkin_photo_path', 'status', 'notes'];

    protected $casts = ['attendance_date' => 'date', 'checked_in_at' => 'datetime', 'checked_out_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(fn (StaffAttendance $attendance) => $attendance->property_id = $attendance->property_id ?: ($attendance->staffMember?->property_id ?: TenantContext::propertyId()));
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}
