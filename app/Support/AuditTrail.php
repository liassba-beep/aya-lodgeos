<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Property;
use App\Models\TenantAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditTrail
{
    public static function register(array $models): void
    {
        foreach ($models as $model) {
            $model::created(fn (Model $record) => self::log(
                $record,
                'created',
                [],
                self::filteredAttributes($record->getAttributes()),
            ));

            $model::updated(function (Model $record): void {
                $changes = self::filteredAttributes($record->getChanges());

                if ($changes === []) {
                    return;
                }

                $oldValues = [];

                foreach (array_keys($changes) as $key) {
                    $oldValues[$key] = $record->getOriginal($key);
                }

                self::log($record, 'updated', $oldValues, $changes);
            });

            $model::deleted(fn (Model $record) => self::log(
                $record,
                'deleted',
                self::filteredAttributes($record->getOriginal()),
                [],
            ));
        }
    }

    public static function logAccessEvent(string $event, ?User $user = null, ?int $propertyId = null, array $metadata = []): void
    {
        AuditLog::create([
            'property_id' => $propertyId,
            'tenant_id' => self::tenantIdFromProperty($propertyId),
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $user?->id,
            'old_values' => null,
            'new_values' => $metadata ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }

    private static function log(Model $record, string $event, array $oldValues, array $newValues): void
    {
        $propertyId = $record->getAttribute('property_id') ?? TenantContext::propertyId();

        AuditLog::create([
            'property_id' => $propertyId,
            'tenant_id' => self::tenantIdForRecord($record, $propertyId),
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $record::class,
            'auditable_id' => $record->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }

    private static function tenantIdForRecord(Model $record, ?int $propertyId): ?int
    {
        if ($record instanceof TenantAccount) {
            return (int) $record->getKey();
        }

        $attributes = $record->getAttributes();

        if (array_key_exists('tenant_id', $attributes) && $record->getAttribute('tenant_id')) {
            return (int) $record->getAttribute('tenant_id');
        }

        if (array_key_exists('tenant_account_id', $attributes) && $record->getAttribute('tenant_account_id')) {
            return (int) $record->getAttribute('tenant_account_id');
        }

        return self::tenantIdFromProperty($propertyId);
    }

    private static function tenantIdFromProperty(?int $propertyId): ?int
    {
        if (! $propertyId) {
            return null;
        }

        $tenantId = Property::query()
            ->whereKey($propertyId)
            ->value('tenant_account_id');

        return $tenantId ? (int) $tenantId : null;
    }

    private static function filteredAttributes(array $attributes): array
    {
        unset(
            $attributes['created_at'],
            $attributes['updated_at'],
            $attributes['deleted_at'],
            $attributes['password'],
            $attributes['remember_token'],
            $attributes['mobile_pin'],
            $attributes['mobile_pin_hash'],
            $attributes['two_factor_secret'],
            $attributes['two_factor_recovery_codes'],
        );

        return $attributes;
    }
}
