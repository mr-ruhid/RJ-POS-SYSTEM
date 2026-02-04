<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    // Bütün sütunların yazılmasına icazə veririk
    protected $guarded = [];

    protected $casts = [
        'grand_total' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'created_at' => 'datetime',
    ];

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
     * [VACİB] İstifadə olunan promokod
     * Bu əlaqə vasitəsilə partnyoru tapırıq
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
