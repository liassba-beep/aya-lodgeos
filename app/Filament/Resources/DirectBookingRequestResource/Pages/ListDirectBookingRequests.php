<?php

namespace App\Filament\Resources\DirectBookingRequestResource\Pages;

use App\Filament\Resources\DirectBookingRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDirectBookingRequests extends ListRecords
{
    protected static string $resource = DirectBookingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
