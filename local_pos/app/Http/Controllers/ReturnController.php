<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    // 1. Axtarış Səhifəsi
    public function index()
    {
        return view('admin.returns.index');
    }

    // 2. Çek Axtarışı və Hesablama
    public function search(Request $request)
    {
        $request->validate([
            'receipt_code' => 'required|string'
        ]);

        $order = Order::with(['items', 'user'])->where('receipt_code', $request->receipt_code)->first();

        if (!$order) {
            return back()->withErrors(['receipt_code' => 'Bu nömrəli çek tapılmadı!']);
        }

        // --- DÜZƏLİŞ: REAL QAYTARMA QİYMƏTİNİN HESABLANMASI ---

        // 1. Səbətdəki məhsulların toplam dəyəri (Məhsul endirimləri çıxılmış halda)
        // Qeyd: OrderItem 'total' sütunu məhsul endirimini artıq nəzərə alıb.
        $basketTotal = $order->items->sum('total');

        // 2. Ümumi Çekə tətbiq olunan əlavə endirim (Promokod və s.)
        // Əgər BasketTotal > GrandTotal, deməli əlavə endirim (Promokod) var.
        $globalDiscount = max(0, $basketTotal - $order->grand_total);

        // 3. Hər məhsulun real ödənilən dəyərini hesablayırıq
        foreach ($order->items as $item) {
            // Məhsulun səbətdəki payı (Faizlə)
            $share = ($basketTotal > 0) ? ($item->total / $basketTotal) : 0;

            // Bu məhsula düşən promokod payı
            $itemGlobalDiscountShare = $globalDiscount * $share;

            // Real Ödənilən Məbləğ = (Məhsulun Yekun Qiyməti - Promokod Payı)
            $realTotalPaid = $item->total - $itemGlobalDiscountShare;

            // Vahid qiyməti (1 ədəd üçün)
            $item->refundable_unit_price = ($item->quantity > 0) ? ($realTotalPaid / $item->quantity) : 0;

            // Qaytarıla biləcək maksimum say
            $item->max_returnable_qty = $item->quantity - $item->returned_quantity;
        }

        return view('admin.returns.create', compact('order'));
    }

    // 3. Qaytarmanı Tamamla
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.quantity' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalRefundAmount = 0;
            $itemsRefunded = false;

            // Təkrar hesablama (Təhlükəsizlik üçün)
            $basketTotal = $order->items->sum('total');
            $globalDiscount = max(0, $basketTotal - $order->grand_total);

            foreach ($request->items as $itemId => $data) {
                $qtyToReturn = (int) $data['quantity'];

                if ($qtyToReturn > 0) {
                    $item = OrderItem::find($itemId);

                    // Limit yoxlanışı
                    $maxReturn = $item->quantity - $item->returned_quantity;
                    if ($qtyToReturn > $maxReturn) {
                        throw new \Exception("Xəta: {$item->product_name} məhsulundan maksimum $maxReturn ədəd qaytara bilərsiniz.");
                    }

                    // Qiymət Hesabı
                    $share = ($basketTotal > 0) ? ($item->total / $basketTotal) : 0;
                    $itemGlobalDiscountShare = $globalDiscount * $share;
                    $realTotalPaid = $item->total - $itemGlobalDiscountShare;
                    $refundableUnitPrice = ($item->quantity > 0) ? ($realTotalPaid / $item->quantity) : 0;

                    $refundAmount = $refundableUnitPrice * $qtyToReturn;

                    // Bazada yeniləmə
                    $item->increment('returned_quantity', $qtyToReturn);

                    // Stoku Bərpa Etmək (Mağazaya)
                    // ProductBatch-i tapıb artırırıq ki, stok düzəlsin
                    $this->restoreStock($item->product_id, $qtyToReturn);

                    $totalRefundAmount += $refundAmount;
                    $itemsRefunded = true;
                }
            }

            if (!$itemsRefunded) {
                return back()->with('error', 'Qaytarılacaq məhsul seçilməyib.');
            }

            // Order-də dəyişikliklər
            $order->increment('refunded_amount', $totalRefundAmount);
            // $order->decrement('paid_amount', $totalRefundAmount); // İstəyə bağlı: Kassa balansını azaltmaq üçün

            // Əgər hamısı qaytarılıbsa statusu dəyiş
            // ... (Status məntiqi əlavə edilə bilər)

            DB::commit();

            return redirect()->route('pos.index')->with('success', 'Qaytarma tamamlandı. Müştəriyə ödəniləcək məbləğ: ' . number_format($totalRefundAmount, 2) . ' ₼');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // Stoku bərpa edən köməkçi funksiya
    private function restoreStock($productId, $qty)
    {
        // 1. Ümumi stoku artır
        Product::where('id', $productId)->increment('quantity', $qty);

        // 2. Partiya (Batch) stokunu artır (Ən son istifadə olunan partiyaya qaytarırıq)
        $batch = ProductBatch::where('product_id', $productId)
                    ->where('location', 'store')
                    ->orderBy('created_at', 'desc')
                    ->first();

        if ($batch) {
            $batch->increment('current_quantity', $qty);
        }
    }
}
