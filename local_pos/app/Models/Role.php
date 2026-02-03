<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Bütün sahələrin doldurulmasına icazə veririk
    protected $guarded = [];

    // JSON olan permissions sütununu avtomatik array-ə çevirir
    protected $casts = [
        'permissions' => 'array',
    ];

    // Bir rolun çoxlu istifadəçisi ola bilər
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
