<?php

namespace App\Filament\Resources\DailyChecklistResource\Pages;

use App\Filament\Resources\DailyChecklistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyChecklists extends ListRecords
{
    protected static string $resource = DailyChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Novo')];
    }
}
