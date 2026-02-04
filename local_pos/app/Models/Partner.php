<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Concerns\HasUuids; // Əgər ID-lər UUID-dirsə bunu aktivləşdirin

class Partner extends Model
{
    use HasFactory;

    // Əgər ID-lər uzun şifrəlidirsə (UUID), aşağıdakı iki sətri aktivləşdirin:
    // use \Illuminate\Database\Eloquent\Concerns\HasUuids;
    // public $incrementing = false;
    // protected $keyType = 'string';

    /**
     * [HƏLL] Bazaya yazılmasına icazə verilən sütunlar.
     * 'commission_percent' burada OLMALIDIR.
     */
    protected $fillable = [
        'name',
        'phone',
        'telegram_chat_id',
        'commission_percent', // <--- Bu sətir çatışmırdı
        'balance',
        'is_active'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'commission_percent' => 'float', // Rəqəm kimi tanıdılır
        'is_active' => 'boolean',
    ];

    /**
     * Partnyorun promokodları
     */
    public function promocodes()
    {
        return $this->hasMany(Promocode::class);
    }
}
