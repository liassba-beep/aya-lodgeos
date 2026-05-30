<?php

namespace App\Filament\Resources\OperationalTaskResource\Pages;

use App\Filament\Resources\OperationalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOperationalTasks extends ListRecords
{
    protected static string $resource = OperationalTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
