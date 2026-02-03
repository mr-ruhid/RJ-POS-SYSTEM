<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    // API Açarını yoxlamaq üçün köməkçi funksiya
    private function checkApiKey($request)
    {
        $serverKey = Setting::where('key', 'server_api_key')->value('value');
        $clientKey = $request->header('X-API-KEY'); // Mağaza bu başlığı göndərməlidir

        if (!$serverKey || $serverKey !== $clientKey) {
            return false;
        }
        return true;
    }

    /**
     * [SERVER ROLU]
     * Mağazadan gələn satışları qəbul edir və Server bazasına yazır.
     */
    public function storeOrders(Request $request)
    {
        // 1. Təhlükəsizlik: API Açarı düzgündürmü?
        if (!$this->checkApiKey($request)) {
            return response()->json(['success' => false, 'message' => 'Yanlış API açarı!'], 401);
        }

        try {
            DB::beginTransaction();

            $ordersData = $request->input('orders'); // Mağazadan gələn satışlar siyahısı

            $syncedCount = 0;

            foreach ($ordersData as $orderData) {
                // Əgər bu satış artıq serverdə varsa (UUID eynidirsə), təkrar yazma
                if (Order::where('id', $orderData['id'])->exists()) {
                    continue;
                }

                // Satışı (Order) yaradırıq
                $order = Order::create([
                    'id' => $orderData['id'], // Mağazadakı UUID eyni qalır
                    'user_id' => 1, // Serverdəki default admin və ya uyğun user
                    'receipt_code' => $orderData['receipt_code'],
                    'lottery_code' => $orderData['lottery_code'],
                    'subtotal' => $orderData['subtotal'],
                    'total_discount' => $orderData['total_discount'],
                    'total_tax' => $orderData['total_tax'],
                    'grand_total' => $orderData['grand_total'],
                    'total_cost' => $orderData['total_cost'],
                    'paid_amount' => $orderData['paid_amount'],
                    'change_amount' => $orderData['change_amount'],
                    'payment_method' => $orderData['payment_method'],
                    'status' => $orderData['status'],
                    'created_at' => $orderData['created_at'], // Mağaza vaxtı
                ]);

                // Satışın məhsullarını (Order Items) yaradırıq
                foreach ($orderData['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'product_barcode' => $item['product_barcode'],
                        'quantity' => $item['quantity'],
                        'is_gift' => $item['is_gift'],
                        'price' => $item['price'],
                        'cost' => $item['cost'],
                        'tax_amount' => $item['tax_amount'],
                        'discount_amount' => $item['discount_amount'],
                        'total' => $item['total'],
                    ]);
                }

                $syncedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$syncedCount satış uğurla sinxronizasiya edildi."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Sync Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server xətası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [SERVER ROLU]
     * Mağaza soruşanda məhsulların siyahısını göndərir (Qiymət yenilənməsi üçün).
     */
    public function getProducts(Request $request)
    {
        if (!$this->checkApiKey($request)) {
            return response()->json(['success' => false, 'message' => 'Yanlış API açarı!'], 401);
        }

        // Bütün aktiv məhsulları və onların kateqoriyalarını göndəririk
        $products = Product::with('category')->where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }
}
