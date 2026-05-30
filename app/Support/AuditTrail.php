<?php

namespace App\Support;

use App\Models\AuditLog;
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

    private static function log(Model $record, string $event, array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'property_id' => $record->property_id ?? TenantContext::propertyId(),
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

    private static function filteredAttributes(array $attributes): array
    {
        unset(
            $attributes['created_at'],
            $attributes['updated_at'],
            $attributes['deleted_at'],
            $attributes['password'],
            $attributes['remember_token'],
        );

        return $attributes;
    }
}
