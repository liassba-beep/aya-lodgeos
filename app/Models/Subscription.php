<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_account_id', 'saas_plan_id', 'status', 'starts_at', 'renews_at', 'ends_at', 'monthly_amount', 'billing_reference', 'notes'];

    protected $casts = ['starts_at' => 'date', 'renews_at' => 'date', 'ends_at' => 'date', 'monthly_amount' => 'decimal:2'];

    public function tenantAccount(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class);
    }

    public function saasPlan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class);
    }
}
