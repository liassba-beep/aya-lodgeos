<?php
namespace App\Filament\Resources\InvoiceLineResource\Pages;
use App\Filament\Resources\InvoiceLineResource; use Filament\Actions; use Filament\Resources\Pages\ListRecords;
class ListInvoiceLines extends ListRecords { protected static string $resource = InvoiceLineResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Nova')]; } }
