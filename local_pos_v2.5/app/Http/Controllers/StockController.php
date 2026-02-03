<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    // 1. Ümumi Stok İcmalı
    public function index()
    {
        $totalStock = ProductBatch::sum('current_quantity');
        $warehouseStock = ProductBatch::where('location', 'warehouse')->sum('current_quantity');
        $storeStock = ProductBatch::where('location', 'store')->sum('current_quantity');

        $products = Product::with(['category', 'batches' => function($query) {
            $query->where('current_quantity', '>', 0);
        }])->orderBy('name')->paginate(20);

        $recentBatches = ProductBatch::with('product')->latest()->take(10)->get();

        return view('admin.stocks.index', compact('totalStock', 'warehouseStock', 'storeStock', 'recentBatches', 'products'));
    }

    // 2. Anbar Stoku
    public function warehouse()
    {
        $batches = ProductBatch::with('product')
                    ->where('current_quantity', '>', 0)
                    ->where('location', 'warehouse')
                    ->latest()
                    ->paginate(20);

        return view('admin.stocks.warehouse', compact('batches'));
    }

    // 3. Mağaza Stoku (DÜZƏLİŞ: Products göndəririk)
    public function store()
    {
        // Mağazada stoku olan məhsulları gətiririk
        $products = Product::whereHas('batches', function($q) {
                // Yalnız mağazada olan və sayı bitməyən partiyalar
                $q->where('location', 'store')->where('current_quantity', '>', 0);
            })
            ->with(['category', 'batches' => function($q) {
                // View-da cəmləyəndə yalnız mağaza partiyalarını görsün
                $q->where('location', 'store')->where('current_quantity', '>', 0);
            }])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.stocks.market', compact('products'));
    }

    // 4. Yeni Mal Qəbulu
    public function create()
    {
        $products = Product::select('id', 'name', 'barcode', 'selling_price')->where('is_active', true)->orderBy('name')->get();
        $taxes = class_exists(Tax::class) ? Tax::where('is_active', true)->get() : [];
        return view('admin.stocks.create', compact('products', 'taxes'));
    }

    // 5. Malı Yadda Saxla
    public function storeData(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'batches' => 'required|array|min:1',
            'batches.*.cost_price' => 'required|numeric|min:0',
            'batches.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->batches as $batchData) {
                $code = ($batchData['variant'] ?? 'Standart') . ' | LOC:warehouse';

                ProductBatch::create([
                    'product_id' => $request->product_id,
                    'cost_price' => $batchData['cost_price'],
                    'initial_quantity' => $batchData['quantity'],
                    'current_quantity' => $batchData['quantity'],
                    'batch_code' => $code,
                    'expiration_date' => $batchData['expiration_date'] ?? null,
                    'location' => 'warehouse' // Məcburi Anbar
                ]);
            }
        });

        return redirect()->route('stocks.index')->with('success', 'Partiyalar uğurla ANBARA qəbul edildi!');
    }

    // --- TRANSFER SİSTEMİ ---

    public function transfer()
    {
        $products = Product::whereHas('batches', function($query) {
            $query->where('location', 'warehouse')->where('current_quantity', '>', 0);
        })->with(['batches' => function($query) {
            $query->where('location', 'warehouse')->where('current_quantity', '>', 0);
        }])->orderBy('name')->get();

        return view('admin.stocks.transfer', compact('products'));
    }

    public function processTransfer(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:product_batches,id',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $warehouseBatch = ProductBatch::lockForUpdate()->findOrFail($request->batch_id);

            if ($warehouseBatch->location !== 'warehouse') {
                 throw \Illuminate\Validation\ValidationException::withMessages([
                    'batch_id' => 'Seçilən partiya anbarda deyil.'
                ]);
            }

            if ($warehouseBatch->current_quantity < $request->quantity) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantity' => 'Anbarda kifayət qədər məhsul yoxdur.'
                ]);
            }

            $warehouseBatch->decrement('current_quantity', $request->quantity);

            $storeBatchCode = str_replace('LOC:warehouse', 'LOC:store', $warehouseBatch->batch_code);
            if ($storeBatchCode === $warehouseBatch->batch_code) {
                $storeBatchCode = $warehouseBatch->batch_code . ' | LOC:store';
            }

            $storeBatch = ProductBatch::where('product_id', $warehouseBatch->product_id)
                            ->where('location', 'store')
                            ->where('batch_code', $storeBatchCode)
                            ->where('cost_price', $warehouseBatch->cost_price)
                            ->first();

            if ($storeBatch) {
                $storeBatch->increment('current_quantity', $request->quantity);
            } else {
                ProductBatch::create([
                    'product_id' => $warehouseBatch->product_id,
                    'cost_price' => $warehouseBatch->cost_price,
                    'initial_quantity' => $request->quantity,
                    'current_quantity' => $request->quantity,
                    'batch_code' => $storeBatchCode,
                    'expiration_date' => $warehouseBatch->expiration_date,
                    'location' => 'store'
                ]);
            }
        });

        return redirect()->route('stocks.market')->with('success', 'Transfer uğurla tamamlandı! Məhsul mağazaya köçürüldü.');
    }

    // --- REDAKTƏ VƏ SİLİNMƏ ---

    public function edit(ProductBatch $batch)
    {
        $taxes = class_exists(Tax::class) ? Tax::where('is_active', true)->get() : [];
        return view('admin.stocks.edit', compact('batch', 'taxes'));
    }

    public function update(Request $request, ProductBatch $batch)
    {
        $request->validate([
            'location' => 'required|in:warehouse,store',
            'cost_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
        ]);

        // Manual redaktə zamanı kod yenilənir
        $variant = explode('|', $batch->batch_code)[0] ?? 'Standart';
        $code = trim($variant) . ' | LOC:' . $request->location;

        $batch->update([
            'cost_price' => $request->cost_price,
            'current_quantity' => $request->quantity,
            'batch_code' => $code,
            'expiration_date' => $request->expiration_date ?? $batch->expiration_date,
            'location' => $request->location
        ]);

        return redirect()->route('stocks.index')->with('success', 'Partiya məlumatları yeniləndi!');
    }

    public function destroy(ProductBatch $batch)
    {
        $batch->delete();
        return redirect()->route('stocks.index')->with('success', 'Partiya silindi.');
    }

    public function updateAlert(Request $request, Product $product)
    {
        $request->validate(['alert_limit' => 'required|integer|min:0']);
        $product->update(['alert_limit' => $request->alert_limit]);
        return back()->with('success', 'Kritik limit yeniləndi.');
    }
}
