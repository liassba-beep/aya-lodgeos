<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalAlert extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'severity', 'title', 'message', 'status', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(fn (OperationalAlert $alert) => $alert->property_id = $alert->property_id ?: TenantContext::propertyId());
    }
}
