<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_barcode',
        'quantity',
        'price',
        'cost',
        'tax_amount',
        'discount_amount',
        'total'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Hansı satışa aiddir
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Hansı məhsuldur
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
