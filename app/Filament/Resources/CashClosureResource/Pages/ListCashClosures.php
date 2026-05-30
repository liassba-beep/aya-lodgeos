<?php
namespace App\Filament\Resources\CashClosureResource\Pages;
use App\Filament\Resources\CashClosureResource; use Filament\Actions; use Filament\Resources\Pages\ListRecords;
class ListCashClosures extends ListRecords { protected static string $resource = CashClosureResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Novo')]; } }
