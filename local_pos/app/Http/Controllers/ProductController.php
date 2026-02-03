<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Məhsul Siyahısı
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(10);
        return view('admin.products.index', compact('products'));
    }

    // Yeni Məhsul Səhifəsi
    public function create()
    {
        $categories = Category::all();
        // Vergi və Maya dəyəri stokda olduğu üçün bura heç nə göndərmirik
        return view('admin.products.create', compact('categories'));
    }

    // Məhsulu Yadda Saxla
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode',
            'category_id' => 'nullable|exists:categories,id',
            'selling_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        // Maya dəyəri və Vergi stokda təyin ediləcək, burada 0 yazırıq ki baza xəta verməsin
        $validated['cost_price'] = 0;
        $validated['tax_rate'] = 0;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Məhsul uğurla əlavə edildi!');
    }

    // --- REDAKTƏ (EDIT) METODLARI ---

    // 1. Redaktə Səhifəsini Açır
    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    // 2. Dəyişiklikləri Yadda Saxlayır
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode,' . $product->id, // Öz ID-sini istisna edirik
            'category_id' => 'nullable|exists:categories,id',
            'selling_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean'
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // Checkbox seçilməyibsə false edirik
        $validated['is_active'] = $request->has('is_active');

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Məhsul məlumatları yeniləndi!');
    }

    // 3. Məhsulu Silir
    public function destroy(Product $product)
    {
        $product->delete();
        return back()->with('success', 'Məhsul silindi.');
    }

    // --- BARKOD ÇAPI ---
    public function barcodes()
    {
        $products = Product::select('id', 'name', 'barcode', 'selling_price')->orderBy('name')->get();
        return view('admin.products.barcodes', compact('products'));
    }
}
