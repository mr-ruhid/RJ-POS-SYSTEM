<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // 1. Bu sətri əlavə edin

class Product extends Model
{
    // 2. Trait-i bura əlavə edin (HasUuids)
    use HasFactory, HasUuids;

    // ID-nin rəqəm yox, string olduğunu bildiririk
    public $incrementing = false;
    protected $keyType = 'string';

    // Bütün sütunlara icazə veririk
    protected $guarded = [];

    // --- ƏLAQƏLƏR ---

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
