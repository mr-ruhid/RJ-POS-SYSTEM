<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

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
