<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Support\TenantContext;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()?->role !== 'super_admin') {
            $data['property_id'] = TenantContext::propertyId();

            if (in_array($data['role'] ?? null, ['super_admin', 'admin'], true)) {
                $data['role'] = 'manager';
            }
        }

        return $data;
    }
}
