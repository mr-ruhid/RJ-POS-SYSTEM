<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PosController extends Controller
{
    // 1. POS Ekranını açır
    public function index()
    {
        $products = Product::where('is_active', true)
            ->with(['activeDiscount', 'batches' => function($q) {
                $q->where('location', 'store')->where('current_quantity', '>', 0);
            }])
            ->latest()
            ->get()
            ->map(function($product) {
                $product->store_stock = $product->batches->sum('current_quantity');
                return $product;
            });

        return view('admin.pos.index', compact('products'));
    }

    // 2. Məhsul Axtarışı
    public function search(Request $request)
    {
        $query = $request->get('q') ?? $request->get('query');

        if (!$query) {
            return response()->json([]);
        }

        $products = Product::with(['category', 'activeDiscount', 'batches' => function($q) {
                $q->where('location', 'store')->where('current_quantity', '>', 0);
            }])
            ->where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->take(10)
            ->get();

        $results = $products->map(function($product) {
            $stock = $product->batches->sum('current_quantity');
            $price = (float) $product->selling_price;
            $discountAmount = 0;

            // --- VERGİ DƏRƏCƏSİNİ batch_code-DAN ÇIXARMAQ ---
            $taxRate = 0;
            $firstBatch = $product->batches->first();

            if ($firstBatch && $firstBatch->batch_code) {
                // Regex: Mötərizə içində rəqəm və % işarəsi axtarır. Məs: (18.00%)
                if (preg_match('/\((\d+(?:\.\d+)?)%\)/', $firstBatch->batch_code, $matches)) {
                    $taxRate = (float) $matches[1];
                }
            }

            if ($product->activeDiscount) {
                $discount = $product->activeDiscount;
                if ($discount->type == 'fixed') {
                    $discountAmount = $discount->value;
                } else {
                    $discountAmount = ($price * $discount->value / 100);
                }
            }

            $finalPrice = $price - $discountAmount;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'image' => $product->image ? asset('storage/' . $product->image) : null,
                'price' => $price,
                'discount_amount' => (float) $discountAmount,
                'final_price' => (float) $finalPrice,
                'tax_rate' => $taxRate,
                'stock' => (int) $stock
            ];
        });

        return response()->json($results);
    }

    // 3. Satışı Tamamla
    public function store(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'payment_method' => 'required|in:cash,card,bonus',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $totalCost = 0;
            $subtotal = 0;
            $totalTax = 0;
            $totalDiscount = 0;
            $grandTotal = 0;

            // İstifadəçi təyini
            $userId = Auth::id();
            if (!$userId) {
                $firstUser = User::first();
                $userId = $firstUser ? $firstUser->id : User::create([
                    'name' => 'Admin',
                    'email' => 'admin@system.local',
                    'password' => Hash::make('admin123')
                ])->id;
            }

            $lotteryCode = method_exists(Order::class, 'generateUniqueLotteryCode')
                            ? Order::generateUniqueLotteryCode()
                            : (string) rand(1000, 9999);

            $order = Order::create([
                'user_id' => $userId,
                'receipt_code' => strtoupper(Str::random(8)),
                'lottery_code' => $lotteryCode,
                'subtotal' => 0,
                'total_discount' => 0,
                'total_tax' => 0,
                'grand_total' => 0,
                'total_cost' => 0,
                'paid_amount' => $request->paid_amount ?? $request->received_amount,
                'payment_method' => $request->payment_method,
                'status' => 'completed'
            ]);

            foreach ($request->cart as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['id']);
                $qtyNeeded = $item['qty'];
                $isGiftRaw = $item['is_gift'] ?? false;
                $isGift = filter_var($isGiftRaw, FILTER_VALIDATE_BOOLEAN);

                $originalPrice = (float) $product->selling_price;

                // --- STOKDAN ÇIXILMA VƏ MAYA ÜZRƏ VERGİ HESABI ---
                // Artıq satış qiymətini göndərməyə ehtiyac yoxdur, maya dəyəri stokun özündə var
                $deductionResult = $this->deductFromStoreStock($product, $qtyNeeded);

                $productTotalCost = $deductionResult['total_cost']; // Ümumi Maya
                $calculatedTotalTax = $deductionResult['total_tax']; // Maya üzərindən hesablanmış Vergi

                $discountAmount = 0;
                $lineTotal = 0;
                $price = $originalPrice;

                if ($isGift) {
                    // Hədiyyə
                    $price = 0;
                    $lineTotal = 0;
                    // Hədiyyə olsa belə, bu malın vergisi şirkət üçün xərcdir (itkidir)
                    $itemTaxAmount = $calculatedTotalTax;

                    // Hesabat Xərci: Maya + Vergi
                    $itemCostForReport = $productTotalCost + $calculatedTotalTax;

                } else {
                    // Normal Satış
                    if ($product->activeDiscount) {
                        $d = $product->activeDiscount;
                        $discountAmount = ($d->type == 'fixed') ? $d->value : ($price * $d->value / 100);
                    }

                    // Yekun qiymət (Müştərinin ödədiyi)
                    $finalUnitTestPrice = $price - $discountAmount;
                    $lineTotal = $finalUnitTestPrice * $qtyNeeded;

                    // Vergi (Maya dəyərindən hesablanmış)
                    $itemTaxAmount = $calculatedTotalTax;

                    // Normal satışda xərc = Maya Dəyəri
                    $itemCostForReport = $productTotalCost;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_barcode' => $product->barcode,
                    'quantity' => $qtyNeeded,
                    'is_gift' => $isGift,
                    'price' => $price,
                    'cost' => ($qtyNeeded > 0) ? ($productTotalCost / $qtyNeeded) : 0,
                    'tax_amount' => $itemTaxAmount,
                    'discount_amount' => $discountAmount * $qtyNeeded,
                    'total' => $lineTotal
                ]);

                $subtotal += ($originalPrice * $qtyNeeded);
                $totalDiscount += ($discountAmount * $qtyNeeded);
                $totalTax += $itemTaxAmount;
                $grandTotal += $lineTotal;

                $totalCost += $itemCostForReport;
            }

            $paidAmount = $request->paid_amount ?? $request->received_amount;

            $order->update([
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'grand_total' => $grandTotal,
                'total_cost' => $totalCost,
                'change_amount' => $paidAmount - $grandTotal
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Satış uğurla tamamlandı!',
                'order_id' => $order->id,
                'receipt_code' => $order->receipt_code,
                'lottery_code' => $order->lottery_code
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Xəta baş verdi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stokdan silir və Maya Dəyərinə əsasən Vergi hesablayır
     */
    private function deductFromStoreStock($product, $qtyNeeded)
    {
        $batches = ProductBatch::where('product_id', $product->id)
            ->where('location', 'store')
            ->where('current_quantity', '>', 0)
            ->orderBy('expiration_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $remainingQty = $qtyNeeded;
        $totalDeductedCost = 0;
        $totalReferenceTax = 0;

        $totalInStore = $batches->sum('current_quantity');

        if ($totalInStore < $qtyNeeded) {
            throw new \Exception("Mağazada '{$product->name}' məhsulundan kifayət qədər yoxdur!");
        }

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) break;

            $take = min($remainingQty, $batch->current_quantity);

            // 1. Maya Dəyəri
            $totalDeductedCost += ($take * $batch->cost_price);

            // 2. Vergi (batch_code içindən Parse edilir)
            $batchTaxRate = 0;
            if (preg_match('/\((\d+(?:\.\d+)?)%\)/', $batch->batch_code, $matches)) {
                $batchTaxRate = (float) $matches[1];
            }

            if ($batchTaxRate > 0) {
                // DÜZƏLİŞ: Vergi MAYA DƏYƏRİNİN faizi kimi hesablanır
                // Vergi = (Maya * Faiz) / 100
                $chunkTax = ($batch->cost_price * ($batchTaxRate / 100)) * $take;
                $totalReferenceTax += $chunkTax;
            }

            $batch->decrement('current_quantity', $take);
            $remainingQty -= $take;
        }

        return [
            'total_cost' => $totalDeductedCost,
            'total_tax' => $totalReferenceTax
        ];
    }
}
