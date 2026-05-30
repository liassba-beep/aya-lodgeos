<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackEntry extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'user_id', 'type', 'title', 'description', 'status', 'priority'];

    protected static function booted(): void
    {
        static::saving(function (FeedbackEntry $feedback) {
            $feedback->property_id = $feedback->property_id ?: TenantContext::propertyId();
            $feedback->user_id = $feedback->user_id ?: auth()->id();
        });
    }
}
