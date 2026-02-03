<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    // Qaytarma Ekranı (Çek axtarışı üçün)
    public function index()
    {
        return view('admin.returns.index');
    }

    // Çeki Axtar və Gətir (Qaytarma üçün seçim ekranı)
    public function search(Request $request)
    {
        $request->validate(['receipt_code' => 'required|string']);

        $order = Order::with(['items', 'user'])
                      ->where('receipt_code', $request->receipt_code)
                      ->first();

        if (!$order) {
            return back()->withErrors(['receipt_code' => 'Çek tapılmadı!']);
        }

        return view('admin.returns.create', compact('order'));
    }

    // Qaytarmanı İcra Et (Process Refund)
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'items' => 'required|array', // Qaytarılacaq məhsullar: [item_id => quantity]
        ]);

        try {
            DB::beginTransaction();

            $totalRefundAmount = 0;
            $itemsReturnedCount = 0;

            foreach ($request->items as $itemId => $qtyToReturn) {
                // Say 0-dan böyükdürsə emal et
                if ($qtyToReturn > 0) {
                    $orderItem = OrderItem::findOrFail($itemId);

                    // Yoxlayırıq: Qaytarmaq istədiyi say, alınan saydan çox ola bilməz
                    // Və ya əvvəl qaytarıbsa, qalan saydan çox ola bilməz
                    $remainingQty = $orderItem->quantity - $orderItem->returned_quantity;

                    if ($qtyToReturn > $remainingQty) {
                        throw new \Exception("Xəta: {$orderItem->product_name} üçün maksimum {$remainingQty} ədəd qaytara bilərsiniz.");
                    }

                    // 1. OrderItem-i yenilə (Qaytarılan sayı artır)
                    $orderItem->increment('returned_quantity', $qtyToReturn);

                    // 2. Pulu hesabla (Bir ədədin faktiki satış qiyməti)
                    // Total / Quantity = Birinin qiyməti (Endirim və vergi daxil)
                    $unitPrice = $orderItem->total / $orderItem->quantity;
                    $refundAmount = $unitPrice * $qtyToReturn;
                    $totalRefundAmount += $refundAmount;

                    // 3. Stoku Geri Qaytar (Mağazaya)
                    // Həmin məhsulun Mağaza (LOC:store) partiyasını tapırıq
                    // Əgər eyni maya dəyəri ilə varsa üstünə gəlirik, yoxdursa yeni yaradırıq

                    // Sadəlik üçün: Ən son mağaza partiyasına əlavə edirik və ya yeni "Return" partiyası yaradırıq
                    $storeBatch = ProductBatch::where('product_id', $orderItem->product_id)
                                    ->where('batch_code', 'like', '%LOC:store%')
                                    ->latest()
                                    ->first();

                    if ($storeBatch) {
                        $storeBatch->increment('current_quantity', $qtyToReturn);
                    } else {
                        // Əgər mağazada partiya yoxdursa (təmizlənibsə), yenisini yaradırıq
                        ProductBatch::create([
                            'product_id' => $orderItem->product_id,
                            'cost_price' => $orderItem->cost, // Satışdakı maya dəyəri
                            'initial_quantity' => $qtyToReturn,
                            'current_quantity' => $qtyToReturn,
                            'batch_code' => 'Return | LOC:store',
                            'expiration_date' => null
                        ]);
                    }

                    $itemsReturnedCount++;
                }
            }

            if ($itemsReturnedCount == 0) {
                return back()->with('error', 'Heç bir məhsul seçilməyib.');
            }

            // 4. Satışın (Order) statusunu yenilə
            $order->increment('refunded_amount', $totalRefundAmount);

            // Tam qaytarılıb yoxsa hissəvi?
            $allReturned = $order->items->every(function ($item) {
                return $item->quantity == $item->returned_quantity;
            });

            if ($allReturned) {
                $order->update(['status' => 'refunded']);
            } else {
                $order->update(['status' => 'partial_refunded']); // Bu statusu bazada enum-a əlavə etmək lazımdır və ya string saxla
            }

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Qaytarma əməliyyatı uğurla tamamlandı! Məbləğ: ' . number_format($totalRefundAmount, 2) . ' ₼');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Xəta: ' . $e->getMessage());
        }
    }
}
