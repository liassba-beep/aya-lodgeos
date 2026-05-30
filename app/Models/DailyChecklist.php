<?php

namespace App\Models;

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
        'evidence_note',
        'notes',
    ];

    protected $casts = [
        'checklist_date' => 'date',
        'completed_at' => 'datetime',
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
