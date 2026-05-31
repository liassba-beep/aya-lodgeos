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
        return collect(self::moduleLabels())
            ->except(['invoice-line'])
            ->all();
    }

    public static function tenantModuleGroupOptions(): array
    {
        $labels = self::tenantModuleLabels();

        return collect(self::tenantNavigationGroups())
            ->filter(fn (?string $group, string $module): bool => array_key_exists($module, $labels))
            ->reject(fn (?string $group, string $module): bool => in_array($module, self::masterOnlyModules(), true))
            ->mapToGroups(fn (?string $group, string $module): array => [($group ?: 'Base') => [$module => $labels[$module] ?? $module]])
            ->map(fn ($items): array => $items->collapse()->all())
            ->all();
    }

    public static function tenantModuleKeys(): array
    {
        return array_keys(self::tenantModuleLabels());
    }

    public static function currentTenantModuleLabels(): array
    {
        $tenant = TenantContext::tenantAccount();

        if (! $tenant || $tenant->enabled_modules === null) {
            return self::tenantModuleLabels();
        }

        return collect(self::tenantModuleLabels())
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

    public static function navigationGroup(string $module): ?string
    {
        if (auth()->user()?->role === 'super_admin') {
            return self::masterNavigationGroups()[$module]
                ?? self::tenantNavigationGroups()[$module]
                ?? null;
        }

        return self::tenantNavigationGroups()[$module] ?? null;
    }

    public static function navigationSort(string $module): ?int
    {
        return self::navigationSorts()[$module] ?? null;
    }

    public static function shouldRegisterNavigation(string $module): bool
    {
        if ($module === 'invoice-line') {
            return false;
        }

        if (auth()->user()?->role !== 'super_admin') {
            return ! in_array($module, self::masterOnlyModules(), true)
                && self::navigationGroup($module) !== 'SaaS';
        }

        return in_array($module, [
            'tenant-account',
            'user',
            'property',
            'subscription',
            'saas-plan',
            'audit-log',
            'feedback-entry',
        ], true);
    }

    private static function masterOnlyModules(): array
    {
        return [
            'saas-plan',
            'subscription',
            'tenant-account',
        ];
    }

    private static function masterNavigationGroups(): array
    {
        return [
            'tenant-account' => 'SaaS',
            'user' => 'SaaS',
            'property' => 'SaaS',
            'subscription' => 'SaaS',
            'saas-plan' => 'SaaS',
            'audit-log' => 'SaaS',
            'feedback-entry' => 'Suporte',
        ];
    }

    private static function tenantNavigationGroups(): array
    {
        return [
            'property' => null,
            'reservation' => 'Reservas',
            'direct-booking-request' => 'Reservas',
            'guest' => 'Reservas',
            'room' => 'Reservas',
            'user' => 'Equipa',
            'mobile-app' => 'Equipa',
            'staff-member' => 'Equipa',
            'staff-schedule' => 'Equipa',
            'staff-attendance' => 'Equipa',
            'staff-leave' => 'Equipa',
            'cash-closure' => 'Financeiro',
            'invoice' => 'Financeiro',
            'payment' => 'Financeiro',
            'receipt' => 'Financeiro',
            'expense' => 'Financeiro',
            'invoice-line' => 'Financeiro',
            'operational-task' => 'Operação',
            'daily-checklist' => 'Operação',
            'maintenance-report' => 'Operação',
            'utility-reading' => 'Operação',
            'remote-approval' => 'Operação',
            'operational-alert' => 'Operação',
            'owner-daily-report' => 'Operação',
            'knowledge-guide' => 'Operação',
            'feedback-entry' => 'Suporte',
            'stock-item' => 'Stock',
            'stock-movement' => 'Stock',
            'product-requisition' => 'Stock',
            'stock-count' => 'Stock',
            'room-inventory' => 'Stock',
            'damage-charge' => 'Stock',
            'audit-log' => 'Administração',
        ];
    }

    private static function navigationSorts(): array
    {
        return [
            'tenant-account' => 1,
            'user' => 2,
            'subscription' => 3,
            'saas-plan' => 4,
            'property' => 5,
            'audit-log' => 90,
            'feedback-entry' => 1,
            'reservation' => 2,
            'direct-booking-request' => 3,
            'guest' => 4,
            'room' => 5,
            'mobile-app' => 3,
            'staff-member' => 4,
            'staff-schedule' => 5,
            'staff-attendance' => 6,
            'staff-leave' => 7,
            'cash-closure' => 1,
            'invoice' => 2,
            'payment' => 3,
            'receipt' => 4,
            'expense' => 5,
            'invoice-line' => 6,
            'operational-task' => 1,
            'daily-checklist' => 2,
            'maintenance-report' => 3,
            'utility-reading' => 4,
            'remote-approval' => 5,
            'operational-alert' => 6,
            'owner-daily-report' => 7,
            'knowledge-guide' => 8,
            'stock-item' => 1,
            'stock-movement' => 2,
            'product-requisition' => 3,
            'stock-count' => 4,
            'room-inventory' => 5,
            'damage-charge' => 6,
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
