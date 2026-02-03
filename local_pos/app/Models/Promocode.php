<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type', // 'store' və ya 'partner'
        'partner_id',
        'discount_type', // 'fixed' və ya 'percent'
        'discount_value',
        'commission_type', // 'fixed' və ya 'percent'
        'commission_value',
        'usage_limit',
        'used_count',
        'expires_at',
        'is_active'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'commission_value' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Kodun sahibi (əgər partnyor kodudursa)
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * HESABATLAR ÜÇÜN VACİBDİR: Bu promokodla edilən satışlar
     * Bu funksiya olmazsa, "Partnyor Hesabatı" səhifəsi xəta verəcək.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Kodun hazırda istifadəyə yararlı olub-olmadığını yoxlayır
     */
    public function isValid()
    {
        // Aktiv deyilsə
        if (!$this->is_active) {
            return false;
        }

        // Vaxtı keçibsə
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Limit dolubsa
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
