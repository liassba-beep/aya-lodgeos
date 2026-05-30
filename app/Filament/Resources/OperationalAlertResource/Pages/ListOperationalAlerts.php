<?php
namespace App\Filament\Resources\OperationalAlertResource\Pages;
use App\Filament\Resources\OperationalAlertResource; use Filament\Actions; use Filament\Resources\Pages\ListRecords;
class ListOperationalAlerts extends ListRecords { protected static string $resource = OperationalAlertResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Novo')]; } }
