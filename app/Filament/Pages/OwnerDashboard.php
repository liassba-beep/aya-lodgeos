<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class OwnerDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Painel';

    protected static ?string $title = 'Painel do proprietário';
}
