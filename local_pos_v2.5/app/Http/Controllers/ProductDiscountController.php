<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Http\Request;

class ProductDiscountController extends Controller
{
    // Endirim Səhifəsi (List)
    public function index()
    {
        // Məhsulları, kateqoriyalarını və aktiv endirimlərini gətiririk
        $products = Product::with(['category', 'activeDiscount'])->orderBy('name')->paginate(20);
        return view('admin.products.discounts', compact('products'));
    }

    // Endirim Təyin Et (Store)
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        // Əgər aktiv endirim varsa, onu dayandırırıq (de-aktiv edirik)
        ProductDiscount::where('product_id', $request->product_id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

        // Yeni endirim yaradırıq
        ProductDiscount::create([
            'product_id' => $request->product_id,
            'type' => $request->type,
            'value' => $request->value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => true
        ]);

        return back()->with('success', 'Endirim uğurla tətbiq edildi!');
    }

    // Endirimi Vaxtından Tez Bitir (Dayandır)
    public function stop(ProductDiscount $discount)
    {
        $discount->update(['is_active' => false, 'end_date' => now()]);
        return back()->with('success', 'Endirim dayandırıldı.');
    }
}
