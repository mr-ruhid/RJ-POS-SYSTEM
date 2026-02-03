<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'type',
        'is_integrated', 'driver_name', 'settings',
        'is_active'
    ];

    protected $casts = [
        'is_integrated' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array' // JSON avtomatik array olur
    ];
}
