<?php

namespace App\Filament\Resources\OperationalTaskResource\Pages;

use App\Filament\Resources\OperationalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOperationalTask extends EditRecord
{
    protected static string $resource = OperationalTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
