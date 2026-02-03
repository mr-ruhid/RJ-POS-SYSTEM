<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // Siyahı və Yaratma Forması (Eyni səhifədə)
    public function index()
    {
        // Yalnız Ana kateqoriyaları (parent_id = null) gətiririk
        // Alt kateqoriyaları "children" ilə çağıracağıq
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();

        // Valideyn seçimi üçün bütün kateqoriyalar lazımdır
        $allCategories = Category::orderBy('name')->get();

        return view('admin.categories.index', compact('categories', 'allCategories'));
    }

    // Yadda Saxla
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048'
        ]);

        $data = $request->all();
        // Slag (URL dostu ad) yaradırıq: "Süd Məhsulları" -> "sud-mehsullari"
        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        return back()->with('success', 'Kateqoriya uğurla yaradıldı!');
    }

    // Redaktə Səhifəsi
    public function edit(Category $category)
    {
        // Özü-özünün valideyni ola bilməz, ona görə siyahıdan çıxarırıq
        $allCategories = Category::where('id', '!=', $category->id)->orderBy('name')->get();
        return view('admin.categories.edit', compact('category', 'allCategories'));
    }

    // Yenilə
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048'
        ]);

        // Sonsuz döngüyə düşməmək üçün yoxlayırıq (Category özü-özünün uşağı ola bilməz)
        if ($request->parent_id == $category->id) {
            return back()->withErrors(['parent_id' => 'Kateqoriya özü-özünün valideyni ola bilməz!']);
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Kateqoriya yeniləndi!');
    }

    // Sil
    public function destroy(Category $category)
    {
        // Alt kateqoriyaları varsa, silmək olmaz (və ya xəbərdarlıq edilməlidir)
        if ($category->children()->count() > 0) {
            return back()->withErrors(['error' => 'Bu kateqoriyanın alt kateqoriyaları var. Əvvəlcə onları silin və ya başqa yerə köçürün.']);
        }

        $category->delete();
        return back()->with('success', 'Kateqoriya silindi.');
    }
}
