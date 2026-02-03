<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'cost_price',
        'initial_quantity',
        'current_quantity',
        'batch_code',
        'expiration_date',
        // YENİ: Malın harda olduğunu bildirən sütun ('warehouse' = Anbar, 'store' = Mağaza)
        'location'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'expiration_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Yalnız Mağazada olan malları gətirən filtr (Scope)
     */
    public function scopeInStore($query)
    {
        return $query->where('location', 'store');
    }

    /**
     * Yalnız Anbarda olan malları gətirən filtr
     */
    public function scopeInWarehouse($query)
    {
        return $query->where('location', 'warehouse');
    }
}
