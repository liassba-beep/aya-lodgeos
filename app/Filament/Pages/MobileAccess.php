<?php

namespace App\Filament\Pages;

use App\Support\AccessControl;
use Filament\Pages\Page;

class MobileAccess extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'App mobile';

    protected static ?string $title = 'Acesso à app mobile';

    protected static string $view = 'filament.pages.mobile-access';

    public static function canAccess(): bool
    {
        return AccessControl::allows('mobile-app', 'view') || AccessControl::allows('*', 'view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && AccessControl::shouldRegisterNavigation('mobile-app');
    }

    public static function getNavigationGroup(): ?string
    {
        return AccessControl::navigationGroup('mobile-app');
    }

    public static function getNavigationSort(): ?int
    {
        return AccessControl::navigationSort('mobile-app');
    }
}
