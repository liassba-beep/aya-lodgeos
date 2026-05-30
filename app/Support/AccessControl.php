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
                'maintenance-report' => $view,
                'utility-reading' => $view,
                'product-requisition' => $view,
                'cash-closure' => $view,
                'remote-approval' => $view,
                'operational-alert' => $view,
                'room-inventory' => $view,
                'damage-charge' => $view,
                'stock-count' => $view,
                'staff-attendance' => $view,
                'staff-leave' => $view,
                'owner-daily-report' => $view,
                'knowledge-guide' => $view,
                'feedback-entry' => $view,
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
                'maintenance-report' => $manage,
                'utility-reading' => $manage,
                'product-requisition' => $manage,
                'cash-closure' => $manage,
                'remote-approval' => $manage,
                'operational-alert' => $manage,
                'room-inventory' => $manage,
                'damage-charge' => $manage,
                'stock-count' => $manage,
                'staff-attendance' => $manage,
                'staff-leave' => $manage,
                'owner-daily-report' => $manage,
                'knowledge-guide' => $manage,
                'feedback-entry' => $manage,
                'direct-booking-request' => $manage,
            ],
            'staff' => [
                'operational-task' => $execute,
                'daily-checklist' => $execute,
                'stock-item' => $view,
                'stock-movement' => ['view', 'create'],
                'maintenance-report' => ['view', 'create'],
                'utility-reading' => ['view', 'create'],
                'product-requisition' => ['view', 'create'],
                'feedback-entry' => ['view', 'create'],
                'knowledge-guide' => $view,
            ],
            'security' => [
                'reservation' => $view,
                'guest' => $view,
                'operational-task' => $execute,
                'daily-checklist' => $execute,
                'maintenance-report' => ['view', 'create'],
            ],
        ];
    }
}
