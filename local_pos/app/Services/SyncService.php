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

    public function pushData()
    {
        if (!$this->serverUrl && !$this->telegramApiUrl) {
            return ['status' => false, 'message' => 'Heç bir server URL təyin edilməyib.'];
        }

        // --------------------------------------------------------
        // 1. STATİSTİKA HESABLANMASI (DƏQİQ)
        // --------------------------------------------------------
        $today = Carbon::today();

        // Bu günün bütün satışlarını detalları ilə gətiririk
        $todayOrders = Order::with('items')->whereDate('created_at', $today)->get();

        $statSales = 0;
        $statCost = 0;
        $statTax = 0;
        $statCommission = 0;
        $statRefunds = 0;

        foreach ($todayOrders as $order) {
            $statSales += $order->grand_total;
            $statRefunds += $order->refunded_amount;
            $statTax += $order->total_tax;
            $statCommission += $order->total_commission;

            // Xalis Maya Dəyəri (Satılan - Qaytarılan)
            foreach ($order->items as $item) {
                // Satılanın mayası
                $statCost += ($item->cost * $item->quantity);

                // Qaytarılanın mayasını xərcdən çıxırıq (Anbara qayıdır)
                if ($item->returned_quantity > 0) {
                    $statCost -= ($item->cost * $item->returned_quantity);
                }
            }
        }

        // Xalis Mənfəət Düsturu: (Satış - Qaytarma) - (Xalis Maya) - Vergi - Komissiya
        $netProfit = ($statSales - $statRefunds) - $statCost - $statTax - $statCommission;
        $netSales = $statSales - $statRefunds;

        // Anbar Dəyəri
        $warehouseCost = DB::table('product_batches')
            ->where('current_quantity', '>', 0)
            ->sum(DB::raw('current_quantity * cost_price'));

        // Partnyor və Kritik Stok
        $partnerCount = class_exists(Partner::class) ? Partner::count() : 0;
        $criticalStockCount = Product::whereRaw('alert_limit > quantity')->count();


        // --------------------------------------------------------
        // 2. SATIŞ SİYAHISI (LİST)
        // --------------------------------------------------------
        $orders = Order::with(['items', 'user', 'promocode.partner'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function($order) {
                $commissionAmount = $order->total_commission ?? 0;
                $partnerId = null;

                if ($order->promocode && $order->promocode->partner) {
                    $partnerId = $order->promocode->partner->id;
                } elseif (!empty($order->promo_code)) {
                    $pCode = Promocode::where('code', $order->promo_code)->first();
                    if($pCode) $partnerId = $pCode->partner_id;
                }

                return [
                    'id' => $order->id,
                    'receipt_code' => $order->receipt_code,
                    'promo_code' => $order->promo_code,
                    'partner_id' => $partnerId,
                    'calculated_commission' => $commissionAmount,
                    'grand_total' => $order->grand_total,
                    'refunded_amount' => $order->refunded_amount ?? 0, // Qaytarma
                    'payment_method' => $order->payment_method,
                    'time' => $order->created_at->format('H:i:s'),
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->toDateTimeString(),
                    'items' => $order->items->toArray()
                ];
            })->toArray();

        // 3. LOTEREYA
        $lotteryOrders = Order::whereNotNull('lottery_code')
            ->where('lottery_code', '!=', '')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function($order) {
                return [
                    'receipt_code' => $order->receipt_code,
                    'lottery_code' => $order->lottery_code,
                    'grand_total' => $order->grand_total,
                    'time' => $order->created_at->format('d.m.Y H:i'),
                ];
            })->toArray();

        // Digər Məlumatlar (Olduğu kimi)
        $products = Product::all()->map(function($p) {
            return [
                'id' => $p->id, 'name' => $p->name, 'barcode' => $p->barcode,
                'selling_price' => $p->selling_price, 'quantity' => $p->quantity ?? 0,
                'cost_price' => $p->cost_price ?? 0
            ];
        })->toArray();

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

        $partners = class_exists(Partner::class) ? Partner::all()->toArray() : [];
        $promocodes = class_exists(Promocode::class) ? Promocode::with('partner')->get()->toArray() : [];

        // 4. YEKUN PAKET
        $payload = [
            'stats' => [
                'today_sales' => $netSales,     // Xalis Satış
                'today_count' => $todayOrders->count(),
                'today_profit' => $netProfit,   // Xalis Mənfəət (Komissiya və Qaytarma çıxılmış)
                'warehouse_cost' => $warehouseCost,
                'partner_count' => $partnerCount,
                'critical_stock' => $criticalStockCount
            ],
            'latest_orders' => $orders,
            'lottery_orders' => $lotteryOrders,
            'products' => $products,
            'batches' => $batches,
            'partners' => $partners,
            'promocodes' => $promocodes
        ];

        // 5. GÖNDƏRİŞ
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

    public function sendSaleNotification($order)
    {
        if (!$this->telegramApiUrl || empty($order->promo_code)) return;
        try {
            $order->load(['promocode.partner']);
            $commissionAmount = $order->total_commission ?? 0;
            $partnerData = null; $promoData = null; $partnerId = null;

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
                    'calculated_commission' => $commissionAmount,
                    'grand_total' => $order->grand_total,
                    'time' => $order->created_at->format('H:i:s'),
                ]],
                'partners' => $partnerData ? [$partnerData] : [],
                'promocodes' => $promoData ? [$promoData] : []
            ];

            Http::timeout(3)->post($this->telegramApiUrl, ['type' => 'telegram_sync', 'api_key' => $this->apiKey, 'payload' => $payload]);
        } catch (\Exception $e) { Log::error("Telegram Notify Error: " . $e->getMessage()); }
    }

    public function sendPartnerWelcome($partner, $promoCode, $discountValue, $commission) { /* ... */ }
    public function pullData() { return ['status' => true, 'message' => 'Monitorinq rejimi aktivdir.']; }
}
