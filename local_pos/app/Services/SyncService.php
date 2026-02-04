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
        // 1. Monitorinq Serveri
        $this->serverUrl = Setting::where('key', 'server_url')->value('value');
        if ($this->serverUrl) {
            $this->serverUrl = rtrim($this->serverUrl, '/');
        }

        // 2. Telegram API Serveri
        $this->telegramApiUrl = Setting::where('key', 'server_telegram_api')->value('value');
        if ($this->telegramApiUrl) {
            $this->telegramApiUrl = rtrim($this->telegramApiUrl, '/');
        }

        $this->apiKey = Setting::where('key', 'client_api_key')->value('value');
    }

    /**
     * [YENİ] ANLIQ SATIŞ BİLDİRİŞİ (Telegram üçün)
     * Satış bitən kimi bu funksiya çağırılır.
     */
    public function sendSaleNotification($order)
    {
        // Yalnız Telegram API varsa və Promokod istifadə olunubsa işləsin
        if (!$this->telegramApiUrl || empty($order->promo_code)) return;

        try {
            // Əlaqəli məlumatları yükləyirik
            $order->load(['promocode.partner']);

            // Komissiya Hesablaması (Təkrar dəqiqlik üçün)
            $commissionAmount = 0;
            $partnerId = null;
            $partnerData = null;
            $promoData = null;

            if ($order->promocode && $order->promocode->partner) {
                $partner = $order->promocode->partner;
                $partnerId = $partner->id;
                $percent = $partner->commission_percent ?? 0;

                if ($percent > 0) {
                    $commissionAmount = ($order->grand_total * $percent) / 100;
                    $commissionAmount = number_format($commissionAmount, 2, '.', '');
                }

                // Partnyor məlumatını paketə qoyuruq ki, server onu tanısın
                $partnerData = $partner->toArray();
                $promoData = $order->promocode->toArray();
            }

            // Yüngül Paket Hazırlayırıq (Sadəcə bu satış üçün)
            $payload = [
                'latest_orders' => [[
                    'id' => $order->id,
                    'receipt_code' => $order->receipt_code,
                    'promo_code' => $order->promo_code,
                    'partner_id' => $partnerId,
                    'calculated_commission' => $commissionAmount, // Hesablanan qazanc
                    'grand_total' => $order->grand_total,
                    'time' => $order->created_at->format('H:i:s'),
                ]],
                // Partnyor və Promokod məlumatını da göndəririk ki,
                // Serverdə yoxdursa belə (sync olunmayıbsa) mesaj gedə bilsin.
                'partners' => $partnerData ? [$partnerData] : [],
                'promocodes' => $promoData ? [$promoData] : []
            ];

            // Telegram API-yə Asinxron (Gözləmədən) göndərməyə çalışırıq
            // Timeout-u qısa qoyuruq ki, kassanı dondurmasın
            Http::timeout(3)->post($this->telegramApiUrl, [
                'type' => 'telegram_sync',
                'api_key' => $this->apiKey,
                'payload' => $payload
            ]);

        } catch (\Exception $e) {
            // İnternet yoxdursa və ya xəta varsa, sadəcə loga yazırıq. Satış dayanmasın.
            Log::error("Telegram Notification Error: " . $e->getMessage());
        }
    }

    /**
     * [UPLOAD] Tam Məlumat Paketi (Manual Sync üçün)
     */
    public function pushData()
    {
        if (!$this->serverUrl && !$this->telegramApiUrl) {
            return ['status' => false, 'message' => 'Heç bir server URL təyin edilməyib.'];
        }

        // --- Məlumatların Toplanması ---
        $orders = Order::with(['items', 'user', 'promocode.partner'])
            ->orderBy('created_at', 'desc')->take(50)->get()
            ->map(function($order) {
                $commissionAmount = 0;
                $partnerId = null;

                if ($order->promocode && $order->promocode->partner) {
                    $partner = $order->promocode->partner;
                    $partnerId = $partner->id;
                    $percent = $partner->commission_percent ?? 0;
                    if ($percent > 0) {
                        $commissionAmount = number_format(($order->grand_total * $percent) / 100, 2, '.', '');
                    }
                }

                return [
                    'id' => $order->id,
                    'receipt_code' => $order->receipt_code,
                    'promo_code' => $order->promo_code,
                    'partner_id' => $partnerId,
                    'calculated_commission' => $commissionAmount,
                    'grand_total' => $order->grand_total,
                    'payment_method' => $order->payment_method,
                    'time' => $order->created_at->format('H:i:s'),
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->toDateTimeString(),
                    'items' => $order->items->toArray()
                ];
            })->toArray();

        $products = Product::all()->map(function($p) {
            return [
                'id' => $p->id, 'name' => $p->name, 'barcode' => $p->barcode,
                'selling_price' => $p->selling_price, 'quantity' => $p->quantity ?? 0,
                'cost_price' => $p->cost_price ?? 0
            ];
        })->toArray();

        $batches = ProductBatch::where('current_quantity', '>', 0)->with('product:id,name')->get()
            ->map(function($b) {
                return [
                    'product_name' => $b->product->name ?? 'Naməlum',
                    'batch_code' => $b->batch_code, 'cost_price' => $b->cost_price,
                    'current_quantity' => $b->current_quantity
                ];
            })->toArray();

        $partners = class_exists(Partner::class) ? Partner::all()->toArray() : [];
        $promocodes = class_exists(Promocode::class) ? Promocode::with('partner')->get()->toArray() : [];

        // Statistika
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

        // Göndəriş
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

    public function sendPartnerWelcome($partner, $promoCode, $discountValue, $commission)
    {
        if (!$this->telegramApiUrl || !$partner->telegram_chat_id) return;
        try {
            $url = str_replace('/telegram-sync', '/partner-welcome', $this->telegramApiUrl);
            if (!str_contains($url, '/api/partner-welcome')) $url = rtrim($this->telegramApiUrl, '/') . '/api/partner-welcome';

            Http::timeout(5)->post($url, [
                'api_key' => $this->apiKey, 'chat_id' => $partner->telegram_chat_id,
                'name' => $partner->name, 'promo_code' => $promoCode,
                'discount' => $discountValue, 'commission' => $commission
            ]);
        } catch (\Exception $e) {}
    }

    public function pullData() { return ['status' => true, 'message' => 'Monitorinq rejimi aktivdir.']; }
}
