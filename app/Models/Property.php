<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tenant_account_id',
        'type',
        'status',
        'invoice_logo_path',
        'legal_name',
        'nuit',
        'email',
        'phone',
        'invoice_phone',
        'invoice_email',
        'address',
        'city',
        'country',
        'invoice_footer',
        'cancellation_policy',
        'deposit_percent',
        'house_rules',
        'cleaning_interval_days',
        'room_inventory_template',
        'meals_and_services',
        'notes',
    ];

    protected $casts = [
        'deposit_percent' => 'decimal:2',
        'room_inventory_template' => 'array',
        'meals_and_services' => 'array',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function tenantAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TenantAccount::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot(['role', 'permissions'])->withTimestamps();
    }
}
