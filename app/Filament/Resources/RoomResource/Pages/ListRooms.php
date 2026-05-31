<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Novo'),
            Actions\Action::make('qrLabels')
                ->label('Imprimir QRs')
                ->icon('heroicon-o-qr-code')
                ->url(fn (): string => route('rooms.qr-labels'))
                ->openUrlInNewTab(),
        ];
    }
}
