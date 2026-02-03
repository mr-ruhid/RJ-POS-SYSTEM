<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id', // Bunu əlavə etdik ki, rol təyin edə bilək
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // İstifadəçinin Rolu (User -> Role)
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Köməkçi funksiya: İstifadəçinin rolunu yoxlamaq üçün
    // Məsələn: $user->hasRole('admin')
    public function hasRole($slug)
    {
        return $this->role && $this->role->slug === $slug;
    }

    // Köməkçi funksiya: İcazəni yoxlamaq üçün
    // Məsələn: $user->hasPermission('products.create')
    public function hasPermission($permission)
    {
        if (!$this->role) return false;

        $permissions = $this->role->permissions ?? [];

        // Əgər 'all' icazəsi varsa (Super Admin)
        if (!empty($permissions['all'])) return true;

        return !empty($permissions[$permission]);
    }
}
