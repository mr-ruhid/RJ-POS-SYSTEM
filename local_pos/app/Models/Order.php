<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // 1. Bu sətri əlavə edin

class Order extends Model
{
    use HasFactory, HasUuids; // 2. Trait-i bura əlavə edin

    // UUID istifadə etdiyimiz üçün bunları qeyd edirik
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * Satışı edən istifadəçi (Kassir)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Satışın içindəki məhsullar
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * İstifadə olunan promokod
     */
    public function promocode()
    {
        return $this->belongsTo(Promocode::class);
    }

    /**
     * Unikal Lotereya Kodu Yaradılması
     */
    public static function generateUniqueLotteryCode()
    {
        do {
            $code = rand(10000000, 99999999);
        } while (self::where('lottery_code', $code)->exists());

        return (string) $code;
    }
}
