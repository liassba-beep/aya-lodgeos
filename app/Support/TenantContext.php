<?php

namespace App\Support;

use App\Models\Property;

class TenantContext
{
    public static function propertyId(): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return Property::query()
                ->where('status', 'active')
                ->orderBy('id')
                ->value('id') ?? Property::query()->orderBy('id')->value('id');
        }

        if ($user->role === 'super_admin') {
            return null;
        }

        $userPropertyId = $user->property_id;

        if ($userPropertyId) {
            return (int) $userPropertyId;
        }

        return $user->properties()
            ->where('status', 'active')
            ->orderBy('properties.id')
            ->value('properties.id');
    }
}
