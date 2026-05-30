<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeGuide extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'category', 'title', 'content', 'status'];

    protected static function booted(): void
    {
        static::saving(fn (KnowledgeGuide $guide) => $guide->property_id = $guide->property_id ?: TenantContext::propertyId());
    }
}
