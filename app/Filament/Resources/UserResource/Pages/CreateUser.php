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
        if (empty($data['password'])) {
            $data['password'] = UserResource::fallbackPassword();
        }

        if (auth()->user()?->role !== 'super_admin') {
            $data['property_id'] = TenantContext::propertyId();

            if (in_array($data['role'] ?? null, ['super_admin', 'admin'], true)) {
                $data['role'] = 'manager';
            }
        } elseif (in_array($data['role'] ?? null, ['super_admin', 'admin'], true)) {
            $data['property_id'] = null;
            $data['web_access_enabled'] = true;
            $data['mobile_access_enabled'] = false;
            $data['permissions'] = null;
        }

        return $data;
    }
}
