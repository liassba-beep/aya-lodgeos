<?php

namespace App\Filament\Widgets;

use App\Models\FeedbackEntry;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\TenantAccount;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaasOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $activeTenants = TenantAccount::query()->where('status', 'active')->count();
        $suspendedTenants = TenantAccount::query()->where('status', 'suspended')->count();
        $properties = Property::query()->count();
        $webUsers = User::query()
            ->where('web_access_enabled', true)
            ->whereNotIn('role', ['super_admin', 'admin'])
            ->count();

        $activeSubscriptions = Subscription::query()
            ->whereIn('status', ['active', 'trial'])
            ->count();
        $mrr = Subscription::query()
            ->whereIn('status', ['active', 'trial'])
            ->sum('monthly_amount');

        $openFeedback = FeedbackEntry::query()
            ->whereNotIn('status', ['done', 'rejected'])
            ->count();

        $tenantsWithoutOwner = TenantAccount::query()
            ->whereDoesntHave('properties.users', fn ($query) => $query->whereIn('role', ['owner', 'manager']))
            ->count();

        $tenantsWithoutModules = TenantAccount::query()
            ->whereNotNull('enabled_modules')
            ->where(function ($query): void {
                $query->whereJsonLength('enabled_modules', 0);
            })
            ->count();

        return [
            Stat::make('Tenants activos', (string) $activeTenants)
                ->description($suspendedTenants.' suspensos')
                ->color($activeTenants > 0 ? 'success' : 'warning'),
            Stat::make('Alojamentos registados', (string) $properties)
                ->description('Propriedades ligadas aos tenants')
                ->color('info'),
            Stat::make('Utilizadores web', (string) $webUsers)
                ->description('Acessos activos de clientes')
                ->color($webUsers > 0 ? 'success' : 'warning'),
            Stat::make('Subscrições activas', (string) $activeSubscriptions)
                ->description('Activas ou em trial')
                ->color($activeSubscriptions > 0 ? 'success' : 'warning'),
            Stat::make('MRR previsto', number_format((float) $mrr, 2).' MZN')
                ->description('Soma mensal das subscrições activas')
                ->color($mrr > 0 ? 'success' : 'gray'),
            Stat::make('Bugs e opiniões abertos', (string) $openFeedback)
                ->description('Pendentes de triagem ou execução')
                ->color($openFeedback > 0 ? 'warning' : 'success'),
            Stat::make('Tenants sem proprietário', (string) $tenantsWithoutOwner)
                ->description('Precisam de acesso inicial')
                ->color($tenantsWithoutOwner > 0 ? 'danger' : 'success'),
            Stat::make('Tenants sem módulos', (string) $tenantsWithoutModules)
                ->description('Sem módulos autorizados')
                ->color($tenantsWithoutModules > 0 ? 'danger' : 'success'),
        ];
    }
}
