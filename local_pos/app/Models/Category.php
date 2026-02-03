<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'image', 'parent_id', 'is_active'];

    // Özü ilə əlaqə: Alt kateqoriyaları çağırmaq üçün
    // Məsələn: $category->children
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Özü ilə əlaqə: Ana kateqoriyanı çağırmaq üçün
    // Məsələn: $subcategory->parent
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Məhsullarla əlaqə
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
