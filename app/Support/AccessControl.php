<?php

namespace App\Support;

class AccessControl
{
    public static function allows(string $module, string $action): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        $permissions = is_array($user->permissions) ? $user->permissions : [];
        $customPermissions = $permissions[$module] ?? null;

        if (is_array($customPermissions) && in_array($action, $customPermissions, true)) {
            return true;
        }

        return in_array($action, self::matrix()[$user->role][$module] ?? [], true);
    }

    public static function roleLabels(): array
    {
        return [
            'super_admin' => 'Super-Admin',
            'admin' => 'Admin',
            'owner' => 'Proprietario',
            'manager' => 'Gerente',
            'staff' => 'Staff',
            'security' => 'Guarda',
        ];
    }

    private static function matrix(): array
    {
        $manage = ['view', 'create', 'update', 'delete'];
        $view = ['view'];
        $execute = ['view', 'update'];

        return [
            'admin' => [
                '*' => $manage,
            ],
            'owner' => [
                'property' => $view,
                'room' => $view,
                'guest' => $view,
                'reservation' => $view,
                'payment' => $view,
                'invoice' => ['view', 'update'],
                'expense' => ['view', 'update'],
                'operational-task' => $view,
                'daily-checklist' => $view,
                'staff-member' => $view,
                'staff-schedule' => $view,
                'stock-item' => $view,
                'stock-movement' => $view,
                'audit-log' => $view,
            ],
            'manager' => [
                'property' => $manage,
                'room' => $manage,
                'guest' => $manage,
                'reservation' => $manage,
                'payment' => $manage,
                'invoice' => $manage,
                'expense' => $manage,
                'operational-task' => $manage,
                'daily-checklist' => $manage,
                'staff-member' => $manage,
                'staff-schedule' => $manage,
                'stock-item' => $manage,
                'stock-movement' => $manage,
                'direct-booking-request' => $manage,
            ],
            'staff' => [
                'operational-task' => $execute,
                'daily-checklist' => $execute,
                'stock-item' => $view,
                'stock-movement' => ['view', 'create'],
            ],
            'security' => [
                'reservation' => $view,
                'guest' => $view,
                'operational-task' => $execute,
                'daily-checklist' => $execute,
            ],
        ];
    }
}
