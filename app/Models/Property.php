<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'invoice_logo_path',
        'legal_name',
        'nuit',
        'email',
        'phone',
        'invoice_phone',
        'invoice_email',
        'address',
        'city',
        'country',
        'invoice_footer',
        'notes',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
