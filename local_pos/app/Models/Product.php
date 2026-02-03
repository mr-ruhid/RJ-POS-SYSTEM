<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'name',
        'barcode',
        'description',
        'category_id',
        'image',
        'cost_price',
        'selling_price',
        'tax_rate',
        'alert_limit', // Kritik stok limiti
        'is_active',
        'last_synced_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'alert_limit' => 'integer',
    ];

    // Məhsulun Kateqoriyası
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Partiyalar (Batches)
    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    // YENİ: Aktiv Endirim (Yalnız 1 dənə və tarixi keçərli olan)
    public function activeDiscount()
    {
        return $this->hasOne(ProductDiscount::class)
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->latest();
    }

    // YENİ: Bütün endirim tarixçəsi
    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class);
    }

    // Köməkçi: Ümumi Stoku Hesablamaq
    public function getTotalStockAttribute()
    {
        return $this->batches()->sum('current_quantity');
    }
}
