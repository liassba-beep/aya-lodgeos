<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_account_id',
        'property_id',
        'user_id',
        'type',
        'title',
        'description',
        'screenshot_path',
        'status',
        'priority',
        'master_response',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (FeedbackEntry $feedback) {
            $feedback->property_id = $feedback->property_id ?: TenantContext::propertyId();
            $feedback->user_id = $feedback->user_id ?: auth()->id();
            $feedback->tenant_account_id = $feedback->tenant_account_id ?: $feedback->property?->tenant_account_id;

            if (in_array($feedback->status, ['done', 'rejected'], true) && ! $feedback->resolved_at) {
                $feedback->resolved_at = now();
            }
        });

        static::created(function (FeedbackEntry $feedback) {
            OperationalAlert::query()->create([
                'property_id' => $feedback->property_id,
                'source_type' => self::class,
                'source_id' => $feedback->id,
                'severity' => $feedback->priority === 'critical' ? 'critical' : ($feedback->priority === 'high' ? 'warning' : 'info'),
                'title' => 'Nova mensagem de tenant',
                'message' => sprintf(
                    '%s enviou %s: %s',
                    $feedback->tenantAccount?->name ?? $feedback->property?->name ?? 'Tenant',
                    self::typeLabels()[$feedback->type] ?? $feedback->type,
                    $feedback->title,
                ),
                'status' => 'open',
            ]);
        });
    }

    public static function typeLabels(): array
    {
        return [
            'bug' => 'Bug',
            'opinion' => 'Opinião',
            'request' => 'Pedido de melhoria',
        ];
    }

    public static function priorityLabels(): array
    {
        return [
            'low' => 'Baixa',
            'normal' => 'Normal',
            'high' => 'Alta',
            'critical' => 'Crítica',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'open' => 'Aberto',
            'triage' => 'Em análise',
            'planned' => 'Planeado',
            'in_progress' => 'Em execução',
            'done' => 'Resolvido',
            'rejected' => 'Rejeitado',
        ];
    }

    public function tenantAccount(): BelongsTo
    {
        return $this->belongsTo(TenantAccount::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
