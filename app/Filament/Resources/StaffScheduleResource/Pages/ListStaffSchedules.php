<?php

namespace App\Filament\Resources\StaffScheduleResource\Pages;

use App\Filament\Resources\StaffScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffSchedules extends ListRecords
{
    protected static string $resource = StaffScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nova escala'),
        ];
    }
}
