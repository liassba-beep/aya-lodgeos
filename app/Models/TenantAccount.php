<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantAccount extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'status', 'enabled_modules', 'billing_email', 'billing_phone', 'notes'];

    protected $casts = [
        'enabled_modules' => 'array',
    ];

    public function hasModule(string $module): bool
    {
        if ($module === '*' || $this->enabled_modules === null) {
            return true;
        }

        return in_array($module, $this->enabled_modules, true);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
