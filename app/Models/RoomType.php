<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'capacity',
        'price_from',
        'amenities_json',
        'photo',
        'sort_order',
    ];

    protected $casts = [
        'price_from' => 'decimal:2',
        'amenities_json' => 'array',
    ];
}
