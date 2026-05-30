<?php

namespace App\Filament\Resources\DirectBookingRequestResource\Pages;

use App\Filament\Resources\DirectBookingRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDirectBookingRequest extends EditRecord
{
    protected static string $resource = DirectBookingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Apagar')];
    }
}
