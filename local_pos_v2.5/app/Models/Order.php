<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'cash_register_id',
        'receipt_code',
        'lottery_code', // 4 rəqəmli kod bura yazılacaq
        'subtotal',
        'total_discount',
        'total_tax',
        'grand_total',
        'total_cost',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    /**
     * Model işə düşərkən işləyən metod.
     * Satış yarananda avtomatik 4 rəqəmli lotoreya kodu təyin edir.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Əgər lotoreya kodu boşdursa, 4 rəqəmli unikal kod yaradırıq
            if (empty($order->lottery_code)) {
                $order->lottery_code = static::generateUniqueLotteryCode();
            }
        });
    }

    /**
     * 4 rəqəmli unikal kod yaradan köməkçi funksiya (1000-9999)
     */
    public static function generateUniqueLotteryCode()
    {
        do {
            $code = (string) rand(1000, 9999);
        } while (static::where('lottery_code', $code)->exists());

        return $code;
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }
}
