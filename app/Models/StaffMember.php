<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'mobile_access_enabled',
        'mobile_pin',
        'mobile_pin_hash',
        'checkin_photo_path',
        'checked_in_at',
        'checked_out_at',
        'last_mobile_login_at',
        'notes',
    ];

    protected $casts = [
        'hired_at' => 'date',
        'mobile_access_enabled' => 'boolean',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'last_mobile_login_at' => 'datetime',
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

    protected function mobilePin(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): array => filled($value) ? ['mobile_pin_hash' => bcrypt($value)] : [],
        );
    }
}
