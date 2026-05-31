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

        if ($module === '*') {
            return $user->role === 'super_admin';
        }

        if (in_array($module, self::masterOnlyModules(), true)) {
            return $user->role === 'super_admin';
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        if (! self::tenantAllows($module)) {
            return false;
        }

        $permissions = is_array($user->permissions) ? $user->permissions : [];
        $customPermissions = $permissions[$module] ?? null;

        $hasCustomPermissions = collect($permissions)
            ->filter(fn ($actions): bool => is_array($actions) && count($actions) > 0)
            ->isNotEmpty();

        if ($hasCustomPermissions) {
            return is_array($customPermissions) && in_array($action, $customPermissions, true);
        }

        $roleMatrix = self::matrix()[$user->role] ?? [];

        return in_array($action, $roleMatrix[$module] ?? [], true)
            || in_array($action, $roleMatrix['*'] ?? [], true);
    }

    public static function roleLabels(): array
    {
        return [
            'super_admin' => 'Super-Admin',
            'admin' => 'Admin',
            'owner' => 'Administrador',
            'manager' => 'Gerente',
            'staff' => 'Staff',
            'security' => 'Guarda',
        ];
    }

    public static function moduleLabels(): array
    {
        return [
            'property' => 'Alojamentos',
            'user' => 'Equipa e acessos',
            'mobile-app' => 'App mobile',
            'room' => 'Quartos',
            'guest' => 'Hóspedes',
            'reservation' => 'Reservas',
            'direct-booking-request' => 'Pedidos directos',
            'payment' => 'Pagamentos',
            'invoice' => 'Facturação',
            'invoice-line' => 'Linhas de factura',
            'receipt' => 'Recibos',
            'expense' => 'Despesas',
            'operational-task' => 'Tarefas operacionais',
            'daily-checklist' => 'Checklists diárias',
            'staff-member' => 'Colaboradores',
            'staff-schedule' => 'Escalas',
            'stock-item' => 'Artigos de stock',
            'stock-movement' => 'Movimentos de stock',
            'maintenance-report' => 'Avarias reportadas',
            'utility-reading' => 'Controlo Credelec',
            'product-requisition' => 'Requisições de produtos',
            'cash-closure' => 'Fecho de caixa',
            'remote-approval' => 'Aprovações remotas',
            'operational-alert' => 'Alertas',
            'room-inventory' => 'Inventário por quarto',
            'damage-charge' => 'Danos e perdas',
            'stock-count' => 'Contagens físicas',
            'staff-attendance' => 'Presenças',
            'staff-leave' => 'Ausências e férias',
            'owner-daily-report' => 'Relatório diário',
            'knowledge-guide' => 'Guia operacional',
            'feedback-entry' => 'Bugs e opiniões',
            'audit-log' => 'Auditoria',
        ];
    }

    public static function tenantModuleLabels(): array
    {
        return self::moduleLabels();
    }

    public static function currentTenantModuleLabels(): array
    {
        $tenant = TenantContext::tenantAccount();

        if (! $tenant || $tenant->enabled_modules === null) {
            return self::moduleLabels();
        }

        return collect(self::moduleLabels())
            ->only($tenant->enabled_modules)
            ->all();
    }

    public static function actionLabels(): array
    {
        return [
            'view' => 'Ver',
            'create' => 'Criar',
            'update' => 'Editar/confirmar',
            'delete' => 'Apagar',
        ];
    }

    private static function masterOnlyModules(): array
    {
        return [
            'saas-plan',
            'subscription',
            'tenant-account',
        ];
    }

    private static function tenantAllows(string $module): bool
    {
        $tenant = TenantContext::tenantAccount();

        return $tenant?->hasModule($module) ?? true;
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
                'user' => ['view', 'create', 'update'],
                'mobile-app' => ['view', 'update'],
                'room' => $view,
                'guest' => $view,
                'reservation' => ['view', 'update'],
                'payment' => $view,
                'invoice' => ['view', 'update'],
                'invoice-line' => $view,
                'receipt' => $view,
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
                'operational-alert' => ['view', 'update'],
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
                'user' => ['view', 'create', 'update'],
                'mobile-app' => $manage,
                'room' => $manage,
                'guest' => $manage,
                'reservation' => $manage,
                'payment' => $manage,
                'invoice' => $manage,
                'invoice-line' => $manage,
                'receipt' => $manage,
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
                'mobile-app' => $view,
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
                'mobile-app' => $view,
                'reservation' => $view,
                'guest' => $view,
                'operational-task' => $execute,
                'daily-checklist' => $execute,
                'maintenance-report' => ['view', 'create'],
            ],
        ];
    }
}
