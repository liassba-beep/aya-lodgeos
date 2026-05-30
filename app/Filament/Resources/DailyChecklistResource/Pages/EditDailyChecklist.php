<?php

namespace App\Filament\Resources\DailyChecklistResource\Pages;

use App\Filament\Resources\DailyChecklistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyChecklist extends EditRecord
{
    protected static string $resource = DailyChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Apagar')];
    }
}
