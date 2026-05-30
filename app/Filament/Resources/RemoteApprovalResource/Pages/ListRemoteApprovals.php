<?php
namespace App\Filament\Resources\RemoteApprovalResource\Pages;
use App\Filament\Resources\RemoteApprovalResource; use Filament\Actions; use Filament\Resources\Pages\ListRecords;
class ListRemoteApprovals extends ListRecords { protected static string $resource = RemoteApprovalResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Nova')]; } }
