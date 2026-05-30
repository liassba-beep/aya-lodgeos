<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_id',
        'staff_member_id',
        'title',
        'priority',
        'status',
        'photo_path',
        'qr_code',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (MaintenanceReport $report) {
            $report->property_id = $report->property_id ?: TenantContext::propertyId();
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}
