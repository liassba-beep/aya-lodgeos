<?php
namespace App\Filament\Resources\SaasPlanResource\Pages;
use App\Filament\Resources\SaasPlanResource; use Filament\Actions; use Filament\Resources\Pages\ListRecords;
class ListSaasPlans extends ListRecords { protected static string $resource = SaasPlanResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Novo')]; } }
