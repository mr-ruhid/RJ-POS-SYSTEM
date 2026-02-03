<?php

// ---------------------------------------------------------
// 2. FAYL: app/Models/CashRegister.php
// Terminalda: php artisan make:model CashRegister
// ---------------------------------------------------------

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'ip_address',
        'balance',
        'status',
        'is_active'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
