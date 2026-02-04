<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductBatch;
use App\Models\User;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PosController extends Controller
{
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

    public function search(Request $request)
    {
        $query = $request->get('q') ?? $request->get('query');

        if (!$query) return response()->json([]);

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
            $taxRate = 0;

            $firstBatch = $product->batches->first();
            if ($firstBatch && $firstBatch->batch_code) {
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

            $finalPrice = max(0, $price - $discountAmount);

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

    // Promokod Yoxlanışı
    public function checkPromo(Request $request)
    {
        $code = $request->get('code');
        $currentTotal = (float) $request->get('total');

        if (!$code) return response()->json(['valid' => false, 'message' => 'Kod daxil edilməyib']);

        $promo = Promocode::where('code', $code)->first();

        if (!$promo) return response()->json(['valid' => false, 'message' => 'Promokod tapılmadı']);
        if (!$promo->is_active) return response()->json(['valid' => false, 'message' => 'Bu promokod aktiv deyil']);
        if ($promo->expires_at && Carbon::parse($promo->expires_at)->isPast()) return response()->json(['valid' => false, 'message' => 'Promokodun vaxtı bitib']);

        // [DÜZƏLİŞ] orders_count -> used_count
        if ($promo->usage_limit && $promo->used_count >= $promo->usage_limit) return response()->json(['valid' => false, 'message' => 'Promokod limiti dolub']);

        $discountAmount = 0;
        if ($promo->discount_type == 'percent') {
            $discountAmount = ($currentTotal * $promo->discount_value) / 100;
        } else {
            $discountAmount = $promo->discount_value;
        }

        $discountAmount = min($discountAmount, $currentTotal);

        return response()->json([
            'valid' => true,
            'discount_amount' => $discountAmount,
            'message' => 'Promokod tətbiq edildi! (-' . number_format($discountAmount, 2) . ' AZN)'
        ]);
    }

    // Satışı Tamamla
    public function store(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'payment_method' => 'required|in:cash,card,bonus',
            'paid_amount' => 'required|numeric|min:0',
            'promo_code' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $totalCost = 0;
            $subtotal = 0;
            $totalTax = 0;
            $totalProductDiscount = 0;
            $grandTotal = 0;

            $userId = Auth::id() ?? 1;

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
                $product = Product::with('activeDiscount')->lockForUpdate()->findOrFail($item['id']);

                $qtyNeeded = $item['qty'];
                $isGiftRaw = $item['is_gift'] ?? false;
                $isGift = filter_var($isGiftRaw, FILTER_VALIDATE_BOOLEAN);

                $originalPrice = (float) $product->selling_price;
                $deductionResult = $this->deductFromStoreStock($product, $qtyNeeded);
                $productTotalCost = $deductionResult['total_cost'];
                $calculatedTotalTax = $deductionResult['total_tax'];

                $discountAmount = 0;
                $price = $originalPrice;
                $lineTotal = 0;

                if ($isGift) {
                    $price = 0;
                    $lineTotal = 0;
                    $itemTaxAmount = $calculatedTotalTax;
                    $itemCostForReport = $productTotalCost + $calculatedTotalTax;
                } else {
                    if ($product->activeDiscount) {
                        $d = $product->activeDiscount;
                        $discountAmount = ($d->type == 'fixed') ? $d->value : ($price * $d->value / 100);
                    }
                    $finalUnitTestPrice = max(0, $price - $discountAmount);
                    $lineTotal = $finalUnitTestPrice * $qtyNeeded;
                    $itemTaxAmount = $calculatedTotalTax;
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
                $totalProductDiscount += ($discountAmount * $qtyNeeded);
                $totalTax += $itemTaxAmount;
                $grandTotal += $lineTotal;
                $totalCost += $itemCostForReport;
            }

            // PROMOKOD VƏ KOMİSSİYA
            $promoDiscount = 0;
            $promoId = null;
            $promoCodeStr = null;
            $totalCommission = 0;

            if ($request->promo_code) {
                $promo = Promocode::where('code', $request->promo_code)->with('partner')->first();

                if ($promo && $promo->is_active) {
                    $isValidDate = (!$promo->expires_at || $promo->expires_at > now());
                    // [DÜZƏLİŞ] orders_count -> used_count
                    $isValidLimit = (!$promo->usage_limit || $promo->used_count < $promo->usage_limit);

                    if ($isValidDate && $isValidLimit) {
                        $promoId = $promo->id;
                        $promoCodeStr = $promo->code;

                        if ($promo->discount_type == 'percent') {
                            $promoDiscount = $grandTotal * ($promo->discount_value / 100);
                        } else {
                            $promoDiscount = $promo->discount_value;
                        }
                        $promoDiscount = min($promoDiscount, $grandTotal);

                        // [DÜZƏLİŞ] İstifadə sayını artırırıq
                        $promo->increment('used_count');

                        // Komissiya Hesablanması
                        if ($promo->partner) {
                            $partner = $promo->partner;
                            $commissionPercent = floatval($partner->commission_percent);

                            if ($commissionPercent > 0) {
                                $finalSaleAmount = max(0, $grandTotal - $promoDiscount);
                                $totalCommission = ($finalSaleAmount * $commissionPercent) / 100;
                                $partner->increment('balance', $totalCommission);
                            }
                        }
                    }
                }
            }

            $grandTotal -= $promoDiscount;
            $totalDiscountAll = $totalProductDiscount + $promoDiscount;
            $finalGrandTotal = max(0, $grandTotal);

            $order->update([
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscountAll,
                'total_tax' => $totalTax,
                'grand_total' => $finalGrandTotal,
                'total_cost' => $totalCost,
                'total_commission' => $totalCommission,
                'change_amount' => ($request->paid_amount ?? 0) - $finalGrandTotal,
                'promo_code' => $promoCodeStr,
                'promocode_id' => $promoId
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Satış uğurla tamamlandı!',
                'order_id' => $order->id,
                'receipt_code' => $order->receipt_code,
                'lottery_code' => $order->lottery_code,
                'promo_applied' => $promoDiscount > 0
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Xəta baş verdi: ' . $e->getMessage()], 500);
        }
    }

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
            $totalDeductedCost += ($take * $batch->cost_price);

            $batchTaxRate = 0;
            if (preg_match('/\((\d+(?:\.\d+)?)%\)/', $batch->batch_code, $matches)) {
                $batchTaxRate = (float) $matches[1];
            }

            if ($batchTaxRate > 0) {
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
