<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'property_id',
        'role',
        'mobile_access_enabled',
        'mobile_pin',
        'mobile_pin_hash',
        'last_mobile_login_at',
        'permissions',
        'locale',
        'theme_mode',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'permissions' => 'array',
            'mobile_access_enabled' => 'boolean',
            'last_mobile_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function mobilePin(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): array => filled($value) ? ['mobile_pin_hash' => bcrypt($value)] : [],
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }

        return in_array($this->role, ['admin', 'owner', 'manager', 'staff', 'security'], true)
            && ($this->property_id || $this->properties()->exists());
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class)->withPivot(['role', 'permissions'])->withTimestamps();
    }
}
