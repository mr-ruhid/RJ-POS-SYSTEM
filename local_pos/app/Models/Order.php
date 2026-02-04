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

    protected $guarded = [];

    protected $casts = [
        'grand_total' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promocode()
    {
        return $this->belongsTo(Promocode::class);
    }

    /**
     * Unikal Lotereya Kodu Yaradılması (5 Rəqəmli)
     */
    public static function generateUniqueLotteryCode()
    {
        do {
            // [DÜZƏLİŞ] 8 rəqəmdən 5 rəqəmə endirildi (10000 - 99999)
            $code = rand(10000, 99999);
        } while (self::where('lottery_code', $code)->exists());

        return (string) $code;
    }
}
