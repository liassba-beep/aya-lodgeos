<?php

namespace App\Filament\Concerns;

use App\Support\AccessControl;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasResourcePermissions
{
    public static function canViewAny(): bool
    {
        return static::allowsResourceAction('view');
    }

    public static function canCreate(): bool
    {
        return static::allowsResourceAction('create');
    }

    public static function canView(Model $record): bool
    {
        return static::allowsResourceAction('view') && static::canAccessTenantRecord($record);
    }

    public static function canEdit(Model $record): bool
    {
        return static::allowsResourceAction('update') && static::canAccessTenantRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::allowsResourceAction('delete') && static::canAccessTenantRecord($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::allowsResourceAction('delete');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny() && AccessControl::shouldRegisterNavigation(static::permissionModuleName());
    }

    public static function getNavigationGroup(): ?string
    {
        return AccessControl::navigationGroup(static::permissionModuleName());
    }

    public static function getNavigationSort(): ?int
    {
        return AccessControl::navigationSort(static::permissionModuleName());
    }

    protected static function allowsResourceAction(string $action): bool
    {
        return AccessControl::allows(static::permissionModuleName(), $action) || AccessControl::allows('*', $action);
    }

    protected static function permissionModuleName(): string
    {
        return property_exists(static::class, 'permissionModule') && static::$permissionModule
            ? static::$permissionModule
            : Str::of(class_basename(static::class))->beforeLast('Resource')->kebab()->toString();
    }

    protected static function canAccessTenantRecord(Model $record): bool
    {
        if (auth()->user()?->role === 'super_admin') {
            return true;
        }

        $attributes = $record->getAttributes();

        if (array_key_exists('property_id', $attributes)) {
            $propertyId = TenantContext::propertyId();

            return $propertyId && (int) $record->getAttribute('property_id') === (int) $propertyId;
        }

        if (array_key_exists('tenant_id', $attributes)) {
            $tenantId = TenantContext::tenantAccount()?->id;

            return $tenantId && (int) $record->getAttribute('tenant_id') === (int) $tenantId;
        }

        return true;
    }
}
