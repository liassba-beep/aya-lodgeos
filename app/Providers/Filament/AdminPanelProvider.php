<?php

namespace App\Providers\Filament;

use App\Filament\Pages\OwnerDashboard;
use App\Filament\Pages\MobileAccess;
use App\Filament\Pages\ReservationCalendar;
use App\Filament\Pages\StaffScheduleCalendar;
use App\Filament\Widgets\OwnerOverview;
use App\Filament\Widgets\SaasOverview;
use App\Http\Middleware\AdminIpAllowlist;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Suporte'),
                NavigationGroup::make()->label('Reservas'),
                NavigationGroup::make()->label('Equipa'),
                NavigationGroup::make()->label('Financeiro'),
                NavigationGroup::make()->label('Operação'),
                NavigationGroup::make()->label('Stock'),
                NavigationGroup::make()->label('Administração'),
                NavigationGroup::make()->label('SaaS'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                OwnerDashboard::class,
                ReservationCalendar::class,
                StaffScheduleCalendar::class,
                MobileAccess::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                SaasOverview::class,
                OwnerOverview::class,
            ])
            ->renderHook(
                'panels::body.end',
                fn (): View => view('filament.hooks.operational-alert-listener'),
            )
            ->renderHook(
                'panels::body.end',
                fn (): View => view('filament.hooks.logout-button'),
            )
            ->middleware([
                AdminIpAllowlist::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
