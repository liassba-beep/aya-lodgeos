<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaasPlan extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'monthly_price', 'property_limit', 'user_limit', 'features', 'status'];

    protected $casts = ['monthly_price' => 'decimal:2', 'features' => 'array'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
