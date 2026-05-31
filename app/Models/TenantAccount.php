<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'enabled_modules',
        'billing_email',
        'billing_phone',
        'whatsapp_number',
        'latitude',
        'longitude',
        'address_label',
        'directions_note',
        'nearby_json',
        'seo_title',
        'seo_description',
        'og_image',
        'favicon_path',
        'notes',
    ];

    protected $casts = [
        'enabled_modules' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'nearby_json' => 'array',
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

    public function photos(): HasMany
    {
        return $this->hasMany(PropertyPhoto::class, 'tenant_id');
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class, 'tenant_id');
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class, 'tenant_id');
    }
}
