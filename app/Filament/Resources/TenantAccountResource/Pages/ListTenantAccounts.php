<?php

namespace App\Filament\Resources\TenantAccountResource\Pages;

use App\Filament\Resources\TenantAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenantAccounts extends ListRecords
{
    protected static string $resource = TenantAccountResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Novo')]; }
}
