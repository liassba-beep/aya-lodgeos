<?php

namespace App\Filament\Resources\PropertyPhotoResource\Pages;

use App\Filament\Resources\PropertyPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPropertyPhotos extends ListRecords
{
    protected static string $resource = PropertyPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nova foto'),
        ];
    }
}
