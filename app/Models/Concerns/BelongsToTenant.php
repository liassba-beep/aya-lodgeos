<?php

namespace App\Models\Concerns;

use App\Models\TenantAccount;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (! auth()->check() || auth()->user()?->role === 'super_admin') {
                return;
            }

            if ($tenant = TenantContext::tenantAccount()) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenant->id);
            }
        });

        static::creating(function ($model): void {
            if (! auth()->check() || $model->tenant_id) {
                return;
            }

            if ($tenant = TenantContext::tenantAccount()) {
                $model->tenant_id = $tenant->id;
            }
        });
    }

    public function tenantAccount(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class, 'tenant_id');
    }
}
