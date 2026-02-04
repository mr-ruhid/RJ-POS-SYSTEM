<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active', 'balance'];

    // Kassa ilə bağlı satışlar (Əgər ehtiyac olsa)
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
