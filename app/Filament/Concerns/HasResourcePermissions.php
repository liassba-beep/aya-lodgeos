<?php

namespace App\Filament\Concerns;

use App\Support\AccessControl;
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
        return static::allowsResourceAction('view');
    }

    public static function canEdit(Model $record): bool
    {
        return static::allowsResourceAction('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::allowsResourceAction('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::allowsResourceAction('delete');
    }

    protected static function allowsResourceAction(string $action): bool
    {
        $module = property_exists(static::class, 'permissionModule') && static::$permissionModule
            ? static::$permissionModule
            : Str::of(class_basename(static::class))->beforeLast('Resource')->kebab()->toString();

        return AccessControl::allows($module, $action) || AccessControl::allows('*', $action);
    }
}
