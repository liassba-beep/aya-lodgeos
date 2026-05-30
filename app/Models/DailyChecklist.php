<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'staff_member_id',
        'checklist_date',
        'area',
        'title',
        'status',
        'completed_at',
        'completed_by_user_id',
        'evidence_note',
        'evidence_photo_path',
        'evidence_latitude',
        'evidence_longitude',
        'evidence_qr_code',
        'notes',
    ];

    protected $casts = [
        'checklist_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (DailyChecklist $checklist) {
            $checklist->property_id = $checklist->property_id ?: TenantContext::propertyId();
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

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
