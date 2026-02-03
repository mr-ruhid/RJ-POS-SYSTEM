<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false; // UUID üçün
    protected $keyType = 'string';

    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class);
    }

    /**
     * Aktiv Endirimi Gətirən Funksiya
     * Yalnız tarixi keçməyən və is_active=1 olan endirimi gətirir.
     */
    public function activeDiscount()
    {
        return $this->hasOne(ProductDiscount::class)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->latest();
    }
}
