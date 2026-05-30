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

        static::created(function (MaintenanceReport $report) {
            OperationalAlert::create([
                'property_id' => $report->property_id,
                'source_type' => MaintenanceReport::class,
                'source_id' => $report->id,
                'severity' => $report->priority === 'critical' ? 'critical' : 'warning',
                'title' => 'Avaria reportada: '.$report->title,
                'message' => trim(collect([
                    $report->room?->name ? 'Quarto: '.$report->room->name : null,
                    $report->staffMember?->name ? 'Reportado por: '.$report->staffMember->name : null,
                    $report->notes,
                ])->filter()->implode("\n")),
                'status' => 'open',
            ]);
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
