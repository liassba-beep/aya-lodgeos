<?php

namespace App\Support;

use App\Models\Property;

class TenantContext
{
    public static function propertyId(): ?int
    {
        $userPropertyId = auth()->user()?->property_id;

        if ($userPropertyId) {
            return (int) $userPropertyId;
        }

        return Property::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->value('id') ?? Property::query()->orderBy('id')->value('id');
    }
}
