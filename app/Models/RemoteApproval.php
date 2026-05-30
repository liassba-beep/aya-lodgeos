<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemoteApproval extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'requested_by', 'approved_by', 'type', 'subject', 'amount', 'status', 'decided_at', 'notes'];

    protected $casts = ['amount' => 'decimal:2', 'decided_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(fn (RemoteApproval $approval) => $approval->property_id = $approval->property_id ?: TenantContext::propertyId());
    }
}
