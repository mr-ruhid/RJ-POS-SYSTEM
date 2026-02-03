<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Http\Request;
use Carbon\Carbon; // Carbon kitabxanası mütləq olmalıdır

class ProductDiscountController extends Controller
{
    // Endirim Səhifəsi (List)
    public function index()
    {
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
            // after:start_date əvəzinə after_or_equal istifadə edirik ki, 1 günlük endirim mümkün olsun
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Mövcud aktiv endirimləri dayandırırıq
        ProductDiscount::where('product_id', $request->product_id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

        // [VACİB DÜZƏLİŞ] Tarixləri saatla birlikdə təyin edirik
        // Start: 00:00:00, End: 23:59:59
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        ProductDiscount::create([
            'product_id' => $request->product_id,
            'type' => $request->type,
            'value' => $request->value,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true
        ]);

        return back()->with('success', 'Endirim uğurla tətbiq edildi!');
    }

    // Endirimi Dayandır
    public function stop(ProductDiscount $discount)
    {
        $discount->update(['is_active' => false, 'end_date' => now()]);
        return back()->with('success', 'Endirim dayandırıldı.');
    }
}
