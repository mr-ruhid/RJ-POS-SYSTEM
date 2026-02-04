<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Setting;
use App\Models\Partner;
use App\Models\Promocode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncService
{
    protected $serverUrl;
    protected $telegramApiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->serverUrl = Setting::where('key', 'server_url')->value('value');
        if ($this->serverUrl) $this->serverUrl = rtrim($this->serverUrl, '/');

        $this->telegramApiUrl = Setting::where('key', 'server_telegram_api')->value('value');
        if ($this->telegramApiUrl) $this->telegramApiUrl = rtrim($this->telegramApiUrl, '/');

        $this->apiKey = Setting::where('key', 'client_api_key')->value('value');
    }

    /**
     * [YENİLƏNMİŞ] Dəqiq Komissiya Göndərən Funksiya
     */
    public function pushData()
    {
        if (!$this->serverUrl && !$this->telegramApiUrl) {
            return ['status' => false, 'message' => 'Heç bir server URL təyin edilməyib.'];
        }

        // 1. SATIŞLAR (Son 50)
        $orders = Order::with(['items', 'user', 'promocode.partner'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function($order) {

                $partnerId = null;
                // Partnyor ID-sini tapırıq (Telegram üçün vacibdir)
                if ($order->promocode && $order->promocode->partner) {
                    $partnerId = $order->promocode->partner->id;
                } elseif (!empty($order->promo_code)) {
                    // Ehtiyat: Əgər ilişki yoxdursa string-dən tap
                    $pCode = Promocode::where('code', $order->promo_code)->first();
                    if($pCode) $partnerId = $pCode->partner_id;
                }

                // [DÜZƏLİŞ] Hesablamaq əvəzinə, birbaşa bazadakı rəqəmi götürürük
                $commissionAmount = $order->total_commission ?? 0;

                return [
                    'id' => $order->id,
                    'receipt_code' => $order->receipt_code,
                    'promo_code' => $order->promo_code,
                    'partner_id' => $partnerId,
                    'calculated_commission' => $commissionAmount, // Bazadakı dəqiq rəqəm
                    'grand_total' => $order->grand_total,
                    'payment_method' => $order->payment_method,
                    'time' => $order->created_at->format('H:i:s'),
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->toDateTimeString(),
                    'items' => $order->items->toArray()
                ];
            })->toArray();

        // 2. MƏHSULLAR
        $products = Product::all()->map(function($p) {
            return [
                'id' => $p->id, 'name' => $p->name, 'barcode' => $p->barcode,
                'selling_price' => $p->selling_price, 'quantity' => $p->quantity ?? 0,
                'cost_price' => $p->cost_price ?? 0
            ];
        })->toArray();

        // 3. ANBAR
        $batches = ProductBatch::where('current_quantity', '>', 0)
            ->with('product:id,name')->get()
            ->map(function($b) {
                return [
                    'product_name' => $b->product->name ?? 'Naməlum',
                    'batch_code' => $b->batch_code, 'cost_price' => $b->cost_price,
                    'current_quantity' => $b->current_quantity,
                    'created_at' => $b->created_at->format('d.m.Y')
                ];
            })->toArray();

        // 4. PARTNYORLAR & PROMOKODLAR
        $partners = class_exists(Partner::class) ? Partner::all()->toArray() : [];
        $promocodes = class_exists(Promocode::class) ? Promocode::with('partner')->get()->toArray() : [];

        // 5. STATİSTİKA
        $today = Carbon::today();
        $todayStats = Order::whereDate('created_at', $today)
            ->select(DB::raw('sum(grand_total) as total_sales'), DB::raw('count(*) as count_sales'), DB::raw('sum(grand_total - total_cost - total_tax) as net_profit'))->first();

        $warehouseCost = DB::table('product_batches')->where('current_quantity', '>', 0)->sum(DB::raw('current_quantity * cost_price'));

        $payload = [
            'stats' => [
                'today_sales' => $todayStats->total_sales ?? 0,
                'today_count' => $todayStats->count_sales ?? 0,
                'today_profit' => $todayStats->net_profit ?? 0,
                'warehouse_cost' => $warehouseCost,
                'partner_count' => count($partners),
            ],
            'latest_orders' => $orders,
            'products' => $products,
            'batches' => $batches,
            'partners' => $partners,
            'promocodes' => $promocodes
        ];

        // GÖNDƏRİŞ
        $messages = [];
        $status = true;

        if ($this->serverUrl) {
            try {
                $response1 = Http::timeout(10)->post($this->serverUrl . '/api/report', ['type' => 'full_report', 'payload' => $payload]);
                if ($response1->successful()) $messages[] = "Monitor OK";
                else { $status = false; $messages[] = "Monitor Xəta"; }
            } catch (\Exception $e) { $status = false; $messages[] = "Monitor Bağlantı"; }
        }

        if ($this->telegramApiUrl) {
            try {
                $response2 = Http::timeout(10)->post($this->telegramApiUrl, ['type' => 'telegram_sync', 'api_key' => $this->apiKey, 'payload' => $payload]);
                if ($response2->successful()) $messages[] = "Telegram OK";
                else $messages[] = "Telegram Xəta: " . $response2->status();
            } catch (\Exception $e) { $messages[] = "Telegram Bağlantı"; }
        }

        return ['status' => $status, 'message' => implode(' | ', $messages)];
    }

    /**
     * Anlıq Satış Bildirişi (Telegram üçün)
     */
    public function sendSaleNotification($order)
    {
        if (!$this->telegramApiUrl || empty($order->promo_code)) return;

        try {
            $order->load(['promocode.partner']);

            // DÜZƏLİŞ: Bazadakı dəqiq rəqəmi götürürük
            $commissionAmount = $order->total_commission ?? 0;

            $partnerData = null;
            $promoData = null;
            $partnerId = null;

            if ($order->promocode && $order->promocode->partner) {
                $partner = $order->promocode->partner;
                $partnerId = $partner->id;
                $partnerData = $partner->toArray();
                $promoData = $order->promocode->toArray();
            }

            $payload = [
                'latest_orders' => [[
                    'id' => $order->id,
                    'receipt_code' => $order->receipt_code,
                    'promo_code' => $order->promo_code,
                    'partner_id' => $partnerId,
                    'calculated_commission' => $commissionAmount, // Dəqiq rəqəm
                    'grand_total' => $order->grand_total,
                    'time' => $order->created_at->format('H:i:s'),
                ]],
                'partners' => $partnerData ? [$partnerData] : [],
                'promocodes' => $promoData ? [$promoData] : []
            ];

            Http::timeout(3)->post($this->telegramApiUrl, [
                'type' => 'telegram_sync',
                'api_key' => $this->apiKey,
                'payload' => $payload
            ]);

        } catch (\Exception $e) {
            Log::error("Telegram Notify Error: " . $e->getMessage());
        }
    }

    // Digər metodlar olduğu kimi qalır
    public function sendPartnerWelcome($partner, $promoCode, $discountValue, $commission) { /* ... */ }
    public function pullData() { return ['status' => true, 'message' => 'Monitorinq rejimi aktivdir.']; }
}
