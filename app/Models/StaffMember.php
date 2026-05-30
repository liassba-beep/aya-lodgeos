<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'role',
        'phone',
        'email',
        'contract_type',
        'hired_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'hired_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (StaffMember $staffMember) {
            $staffMember->property_id = $staffMember->property_id ?: TenantContext::propertyId();
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function operationalTasks(): HasMany
    {
        return $this->hasMany(OperationalTask::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }
}
