<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServerSyncController extends Controller
{
    /**
     * Təhlükəsizlik: API Açarını Bazadan Yoxlayır
     */
    private function checkApiKey($request)
    {
        try {
            // Serverin özündə olan açarı götürürük
            $serverKey = Setting::where('key', 'server_api_key')->value('value');

            // Sorğudan gələn açarı götürürük
            $clientKey = $request->header('X-API-KEY') ?? $request->input('api_key');

            // Əgər server rejimi aktiv deyilsə və ya açar yoxdursa
            if (!$serverKey) return false;

            // Açarlar uyğun gəlmirsə
            if ($serverKey !== $clientKey) return false;

            return true;
        } catch (\Throwable $e) {
            Log::error("API Key Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * [UPLOAD] YUXARI AXIN: Mağazadan Gələn Məlumatları Qəbul Edir
     * Bu metod Mağazadakı məlumatların eynisini Serverdə yaradır (Güzgü Effekti).
     */
    public function uploadData(Request $request)
    {
        // 1. Təhlükəsizlik Yoxlanışı
        if (!$this->checkApiKey($request)) {
            return response()->json(['status' => false, 'message' => 'Yanlış API açarı!'], 401);
        }

        // 2. Məlumatların Alınması
        $payload = $request->all();
        $orders = $payload['orders'] ?? [];
        $products = $payload['products'] ?? [];

        // Əgər heç bir məlumat yoxdursa, sadəcə 200 qaytarırıq (Ping məqsədli)
        if (empty($orders) && empty($products)) {
            return response()->json(['status' => true, 'message' => 'Məlumat boşdur, əlaqə var.'], 200);
        }

        DB::beginTransaction();
        try {
            $stats = ['products_processed' => 0, 'orders_processed' => 0];

            // ---------------------------------------------------------
            // A. MƏHSULLARIN TAM SİNXRONİZASİYASI (Local -> Server)
            // ---------------------------------------------------------
            // Mağaza özündə olan məhsulları və stokları göndərir.
            // Server bu məlumatları olduğu kimi qəbul edir.
            foreach ($products as $prodData) {
                Product::updateOrCreate(
                    ['id' => $prodData['id']], // UUID eyniləşdirmə
                    [
                        'name' => $prodData['name'],
                        'barcode' => $prodData['barcode'],
                        'category_id' => $prodData['category_id'] ?? null,
                        'cost_price' => $prodData['cost_price'] ?? 0,
                        'selling_price' => $prodData['selling_price'],
                        // Mağazadakı stoku Serverə yazırıq.
                        // Qeyd: Bazada 'quantity' sütunu olmalıdır (database_fix.sql işlədilibsə)
                        'quantity' => $prodData['quantity'] ?? 0,
                        'tax_rate' => $prodData['tax_rate'] ?? 0,
                        'alert_limit' => $prodData['alert_limit'] ?? 5,
                        'is_active' => $prodData['is_active'] ?? true,
                        // Serverdəki vaxtı yeniləyirik ki, digər kassalar bunu "yeni" kimi görsün
                        'updated_at' => now()
                    ]
                );
                $stats['products_processed']++;
            }

            // ---------------------------------------------------------
            // B. SATIŞLARIN YAZILMASI
            // ---------------------------------------------------------
            foreach ($orders as $orderData) {
                // Təkrarçılıq yoxlanışı: Əgər satış artıq varsa, onu atlayırıq
                if (Order::where('id', $orderData['id'])->exists()) continue;

                // Qəbz kodu unikal olmalıdır, serverdə varsa atlayırıq
                if (!empty($orderData['receipt_code']) && Order::where('receipt_code', $orderData['receipt_code'])->exists()) {
                    continue;
                }

                // Satış Başlığı (Orders)
                $order = Order::create([
                    'id' => $orderData['id'],
                    'user_id' => 1, // Server Admininə bağlayırıq (Xəta olmasın deyə)
                    'receipt_code' => $orderData['receipt_code'] ?? null,
                    'lottery_code' => $orderData['lottery_code'] ?? null,
                    'promo_code' => $orderData['promo_code'] ?? null,
                    'grand_total' => $orderData['grand_total'],
                    'subtotal' => $orderData['subtotal'],
                    'total_tax' => $orderData['total_tax'] ?? 0,
                    'total_discount' => $orderData['total_discount'] ?? 0,
                    'total_cost' => $orderData['total_cost'] ?? 0,
                    'paid_amount' => $orderData['paid_amount'],
                    'change_amount' => $orderData['change_amount'],
                    'payment_method' => $orderData['payment_method'],
                    'status' => $orderData['status'] ?? 'completed',
                    'created_at' => $orderData['created_at'],
                    'updated_at' => now(),
                ]);

                // Satış Məhsulları (Order Items)
                if (isset($orderData['items']) && is_array($orderData['items'])) {
                    foreach ($orderData['items'] as $item) {

                        // [VACİB] Foreign Key Xətası (1452) olmasın deyə yoxlayırıq.
                        // Əgər nədənsə məhsul siyahıda yoxdursa, müvəqqəti yaradırıq.
                        if (!Product::where('id', $item['product_id'])->exists()) {
                            try {
                                Product::create([
                                    'id' => $item['product_id'],
                                    'name' => $item['product_name'] ?? 'Naməlum Məhsul',
                                    'barcode' => $item['product_barcode'] ?? uniqid(),
                                    'selling_price' => $item['price'] ?? 0,
                                    'quantity' => 0,
                                    'is_active' => 0
                                ]);
                            } catch (\Throwable $pEx) {
                                // Məhsul yaradıla bilmədisə bu item-i keçirik
                                continue;
                            }
                        }

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'product_name' => $item['product_name'],
                            'product_barcode' => $item['product_barcode'],
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                            'total' => $item['total'],
                            'cost' => $item['cost'] ?? 0,
                            'tax_amount' => $item['tax_amount'] ?? 0,
                            'discount_amount' => $item['discount_amount'] ?? 0,
                            'is_gift' => $item['is_gift'] ?? 0,
                        ]);

                        // QEYD: Biz yuxarıda (A bölməsində) məhsulun stokunu Mağazadakı ilə eyniləşdirdik.
                        // Ona görə də burada `decrement` etməyə ehtiyac yoxdur.
                        // Mağaza bizə "Satışdan sonra qalan sayı" göndərir.
                    }
                }
                $stats['orders_processed']++;
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Məlumatlar Serverə tam yazıldı.",
                'stats' => $stats
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("SYNC UPLOAD ERROR: " . $e->getMessage());
            // Xətanı tam qaytarırıq ki, client görə bilsin
            return response()->json([
                'status' => false,
                'message' => 'Server Xətası: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * [DOWNLOAD] AŞAĞI AXIN: Serverdən Məlumatları Gətir
     * Kassa soruşur: "Məndə filan vaxtdan bəri məlumat yoxdur, yeniləri ver"
     */
    public function downloadData(Request $request)
    {
        if (!$this->checkApiKey($request)) {
            return response()->json(['status' => false, 'message' => 'Yanlış API açarı!'], 401);
        }

        try {
            $lastSync = $request->input('last_sync_time');
            $query = Product::with('category'); // Kateqoriya ilə birlikdə gətir

            // Əgər vaxt göndərilibsə, yalnız yeni/dəyişən məhsulları göndər (Delta Sync)
            if ($lastSync) {
                $query->where('updated_at', '>', $lastSync);
            }

            $products = $query->get();

            return response()->json([
                'status' => true,
                'server_time' => now()->toDateTimeString(),
                'products' => $products,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
