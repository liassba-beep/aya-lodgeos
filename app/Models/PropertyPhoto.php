<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyPhoto extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'path',
        'alt',
        'caption',
        'category',
        'sort_order',
    ];

    public static function categoryOptions(): array
    {
        return [
            'quarto' => 'Quarto',
            'casa-de-banho' => 'Casa de banho',
            'kitnet' => 'Kitnet',
            'refeicoes' => 'Refeições',
            'exterior' => 'Exterior',
            'envolvente' => 'Envolvente',
        ];
    }
}
