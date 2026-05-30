<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLeave extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'staff_member_id', 'type', 'starts_at', 'ends_at', 'status', 'reason'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date'];

    protected static function booted(): void
    {
        static::saving(fn (StaffLeave $leave) => $leave->property_id = $leave->property_id ?: ($leave->staffMember?->property_id ?: TenantContext::propertyId()));
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}
