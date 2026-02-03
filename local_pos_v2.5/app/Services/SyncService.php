<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Promocode;
use Illuminate\Support\Facades\Http;

class SyncService
{
    protected $serverUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->serverUrl = Setting::where('key', 'server_url')->value('value');
        $this->apiKey = Setting::where('key', 'client_api_key')->value('value');

        if ($this->serverUrl) {
            $this->serverUrl = rtrim($this->serverUrl, '/');
        }
    }

    /**
     * [UPLOAD] YUXARI AXIN: Məlumatları Serverə Göndər
     */
    public function pushData()
    {
        $mode = Setting::where('key', 'system_mode')->value('value');
        if ($mode !== 'client') return ['status' => false, 'message' => 'Bu cihaz Mağaza rejimində deyil.'];
        if (!$this->serverUrl || !$this->apiKey) return ['status' => false, 'message' => 'Server məlumatları yoxdur.'];

        // 1. Satışlar
        $orders = Order::with('items')->orderBy('created_at', 'desc')->take(50)->get()->toArray();

        // 2. Məhsullar + PARTİYALAR (Batches) - Anbarın düzgün getməsi üçün
        // 'batches' əlaqəsi Product modelində olmalıdır
        $products = Product::with('batches')->get()->toArray();

        // 3. Promokodlar
        $promocodes = class_exists(Promocode::class) ? Promocode::all()->toArray() : [];

        try {
            $response = Http::withHeaders(['X-API-KEY' => $this->apiKey, 'Accept' => 'application/json'])
                ->timeout(120)
                ->post($this->serverUrl . '/api/v1/sync/upload', [
                    'orders' => $orders,
                    'products' => $products,
                    'promocodes' => $promocodes
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // Serverdən gələn stok yenilənmələrini tətbiq edirik
                if (!empty($data['updated_stocks'])) {
                    foreach ($data['updated_stocks'] as $stockData) {
                        Product::where('id', $stockData['id'])->update(['quantity' => $stockData['quantity']]);
                    }
                }
                return ['status' => true, 'message' => $data['message'] ?? 'Uğurlu'];
            }

            return ['status' => false, 'message' => 'Server Xətası: ' . substr($response->body(), 0, 500)];

        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Bağlantı xətası: ' . $e->getMessage()];
        }
    }

    /**
     * [DOWNLOAD] AŞAĞI AXIN: Serverdən Yenilikləri Çək
     */
    public function pullData()
    {
        $mode = Setting::where('key', 'system_mode')->value('value');
        if ($mode !== 'client') return ['status' => false, 'message' => 'Client deyil'];

        try {
            $lastSync = Setting::where('key', 'last_sync_time')->value('value');
            $response = Http::withHeaders(['X-API-KEY' => $this->apiKey, 'Accept' => 'application/json'])
                ->get($this->serverUrl . '/api/v1/sync/download', ['last_sync_time' => $lastSync]);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['products'])) {
                    foreach ($data['products'] as $pData) {

                        // [HƏLL] BARKOD TOQQUŞMASINI YOXLAYIRIQ
                        // Əgər serverdən gələn barkod bizdə başqa bir ID ilə varsa, bizimkini dəyişirik.
                        // Çünki Server "Master" sayılır.
                        $conflictProduct = Product::where('barcode', $pData['barcode'])
                                                  ->where('id', '!=', $pData['id'])
                                                  ->first();

                        if ($conflictProduct) {
                            $conflictProduct->barcode = $pData['barcode'] . '_OLD_' . rand(1000, 9999);
                            $conflictProduct->is_active = false;
                            $conflictProduct->save();
                        }

                        Product::updateOrCreate(
                            ['id' => $pData['id']],
                            [
                                'name' => $pData['name'],
                                'barcode' => $pData['barcode'],
                                'selling_price' => $pData['selling_price'],
                                // [DÜZƏLİŞ] Cost price əlavə edildi
                                'cost_price' => $pData['cost_price'] ?? 0,
                                'quantity' => $pData['quantity'] ?? 0,
                                'category_id' => $pData['category_id'] ?? null,
                                'is_active' => $pData['is_active'] ?? true,
                                'updated_at' => now()
                            ]
                        );
                    }
                }

                if (isset($data['server_time'])) {
                    Setting::updateOrCreate(['key' => 'last_sync_time'], ['value' => $data['server_time']]);
                }

                return ['status' => true, 'message' => (count($data['products'] ?? []) . ' məhsul yeniləndi')];
            }

            return ['status' => false, 'message' => 'Server Xətası: ' . substr($response->body(), 0, 500)];

        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Bağlantı xətası: ' . $e->getMessage()];
        }
    }
}
