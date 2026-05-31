<?php

namespace App\Filament\Resources\TenantAccountResource\Pages;

use App\Filament\Resources\TenantAccountResource;
use App\Models\TenantAccount;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateTenantAccount extends CreateRecord
{
    protected static string $resource = TenantAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $createAccess = (bool) ($data['onboarding_create_access'] ?? false);
        $propertyName = $data['onboarding_property_name'] ?? null;
        $ownerName = $data['onboarding_owner_name'] ?? null;
        $ownerEmail = $data['onboarding_owner_email'] ?? null;
        $ownerPhone = $data['onboarding_owner_phone'] ?? null;
        $ownerPassword = $data['onboarding_owner_password'] ?? null;

        unset(
            $data['onboarding_create_access'],
            $data['onboarding_property_name'],
            $data['onboarding_owner_name'],
            $data['onboarding_owner_email'],
            $data['onboarding_owner_phone'],
            $data['onboarding_owner_password'],
        );

        return DB::transaction(function () use ($data, $createAccess, $propertyName, $ownerName, $ownerEmail, $ownerPhone, $ownerPassword): TenantAccount {
            $tenant = TenantAccount::query()->create($data);

            if (! $createAccess) {
                return $tenant;
            }

            TenantAccountResource::createInitialAccess($tenant, [
                'property_name' => $propertyName,
                'owner_name' => $ownerName,
                'owner_email' => $ownerEmail,
                'owner_phone' => $ownerPhone,
                'owner_password' => $ownerPassword,
            ]);

            return $tenant;
        });
    }
}
