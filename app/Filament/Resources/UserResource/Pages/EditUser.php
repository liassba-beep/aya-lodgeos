<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Support\TenantContext;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Apagar')];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (auth()->user()?->role !== 'super_admin') {
            $data['property_id'] = TenantContext::propertyId();

            if (in_array($data['role'] ?? null, ['super_admin', 'admin'], true)) {
                $data['role'] = 'manager';
            }
        }

        return $data;
    }
}
