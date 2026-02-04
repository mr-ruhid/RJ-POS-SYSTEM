<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_code',
        'customer_id',
        'promocode_id',
        'subtotal',
        'discount_amount',
        'total_tax',
        'total_cost',
        'grand_total',
        'payment_method',
        'payment_status',
        'change_amount',
        'lottery_code',
        'notes'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    // İlişkilər (Relationships)

    /**
     * Sifarişə aid promokod
     */
    public function promocode()
    {
        return $this->belongsTo(Promocode::class, 'promocode_id');
    }

    /**
     * Sifarişə aid müştəri
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Sifarişə aid məhsullar
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Sifarişə aid ödənişlər
     */
    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }
}
