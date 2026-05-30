<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerDailyReport extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'report_date', 'revenue', 'expenses', 'arrivals', 'departures', 'occupied_rooms', 'open_tasks', 'open_alerts', 'status', 'summary'];

    protected $casts = ['report_date' => 'date'];

    protected static function booted(): void
    {
        static::saving(fn (OwnerDailyReport $report) => $report->property_id = $report->property_id ?: TenantContext::propertyId());
    }
}
