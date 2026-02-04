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
        // 1. Monitorinq Serveri (Node.js)
        $this->serverUrl = Setting::where('key', 'server_url')->value('value');
        if ($this->serverUrl) {
            $this->serverUrl = rtrim($this->serverUrl, '/');
        }

        // 2. Telegram API Serveri (İkinci Ünvan)
        $this->telegramApiUrl = Setting::where('key', 'server_telegram_api')->value('value');
        if ($this->telegramApiUrl) {
            $this->telegramApiUrl = rtrim($this->telegramApiUrl, '/');
        }

        // API Key (Əgər serverdə yoxlama varsa)
        $this->apiKey = Setting::where('key', 'client_api_key')->value('value');
    }

    /**
     * [UPLOAD] Bütün Məlumatları Hər İki Ünvana Göndərir
     */
    public function pushData()
    {
        // Yoxlama: Heç olmasa biri mövcud olmalıdır
        if (!$this->serverUrl && !$this->telegramApiUrl) {
            return ['status' => false, 'message' => 'Heç bir server URL (Monitor və ya Telegram) təyin edilməyib.'];
        }

        // --------------------------------------------------------
        // 1. MƏLUMATLARIN TOPLANMASI (DATA COLLECTION)
        // --------------------------------------------------------

        // A. GÜNLÜK STATİSTİKA
        $today = Carbon::today();
        $todayStats = Order::whereDate('created_at', $today)
            ->select(
                DB::raw('sum(grand_total) as total_sales'),
                DB::raw('count(*) as count_sales'),
                DB::raw('sum(grand_total - total_cost - total_tax) as net_profit')
            )->first();

        // B. SATIŞLAR (Son 50)
        $orders = Order::with(['items', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'receipt_code' => $order->receipt_code,
                    'lottery_code' => $order->lottery_code,
                    'promo_code' => $order->promo_code,
                    'grand_total' => $order->grand_total,
                    'total_cost' => $order->total_cost,
                    'payment_method' => $order->payment_method,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toDateTimeString(),
                    'time' => $order->created_at->format('H:i:s'),
                    'user_name' => $order->user ? $order->user->name : 'Sistem',
                    'items_count' => $order->items->count(),
                    'items' => $order->items->toArray()
                ];
            })->toArray();

        // C. MƏHSULLAR (Tam siyahı - Stok və Qiymət yeniləməsi üçün)
        $products = Product::all()->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'barcode' => $p->barcode,
                'selling_price' => $p->selling_price,
                'cost_price' => $p->cost_price ?? 0,
                'quantity' => $p->quantity ?? 0,
                'alert_limit' => $p->alert_limit,
                'is_active' => $p->is_active,
                'category_id' => $p->category_id
            ];
        })->toArray();

        // D. ANBAR PARTİYALARI
        $batches = ProductBatch::where('current_quantity', '>', 0)
            ->with('product:id,name')
            ->get()
            ->map(function($b) {
                return [
                    'product_name' => $b->product->name ?? 'Naməlum',
                    'batch_code' => $b->batch_code,
                    'cost_price' => $b->cost_price,
                    'current_quantity' => $b->current_quantity,
                    'created_at' => $b->created_at->format('d.m.Y')
                ];
            })->toArray();

        // Anbarın ümumi maya dəyəri
        $warehouseCost = DB::table('product_batches')
            ->where('current_quantity', '>', 0)
            ->sum(DB::raw('current_quantity * cost_price'));

        // E. PARTNYORLAR VƏ PROMOKODLAR
        $partners = class_exists(Partner::class) ? Partner::all()->toArray() : [];
        $promocodes = class_exists(Promocode::class) ? Promocode::with('partner')->get()->toArray() : [];
        $criticalStockCount = Product::whereRaw('alert_limit > quantity')->count();

        // --------------------------------------------------------
        // 2. PAKETİN HAZIRLANMASI
        // --------------------------------------------------------
        $payload = [
            'stats' => [
                'today_sales' => $todayStats->total_sales ?? 0,
                'today_count' => $todayStats->count_sales ?? 0,
                'today_profit' => $todayStats->net_profit ?? 0,
                'warehouse_cost' => $warehouseCost,
                'partner_count' => count($partners),
                'critical_stock' => $criticalStockCount
            ],
            'latest_orders' => $orders,
            'products' => $products,
            'batches' => $batches,
            'partners' => $partners,
            'promocodes' => $promocodes
        ];

        // --------------------------------------------------------
        // 3. GÖNDƏRİŞ PROSESİ (DUAL SEND)
        // --------------------------------------------------------
        $messages = [];
        $status = true;

        // A. Monitora Göndər (Node.js /api/report)
        if ($this->serverUrl) {
            try {
                $response1 = Http::timeout(10)->post($this->serverUrl . '/api/report', [
                    'type' => 'full_report',
                    'payload' => $payload
                ]);

                if ($response1->successful()) {
                    $messages[] = "Monitor OK";
                } else {
                    $status = false; // Birində xəta olsa ümumi statusu xəta göstəririk
                    $messages[] = "Monitor Xətası: " . $response1->status();
                }
            } catch (\Exception $e) {
                $status = false;
                $messages[] = "Monitor Bağlantı Xətası";
            }
        }

        // B. Telegram API-yə Göndər (Əlavə ünvan)
        if ($this->telegramApiUrl) {
            try {
                // Telegram API-yə eyni paketi göndəririk
                // Məsələn: https://api.server.com/telegram-sync
                $response2 = Http::timeout(10)->post($this->telegramApiUrl, [
                    'type' => 'telegram_sync',
                    'api_key' => $this->apiKey, // Təhlükəsizlik üçün açar
                    'payload' => $payload
                ]);

                if ($response2->successful()) {
                    $messages[] = "Telegram API OK";
                } else {
                    // Telegram API xətası əsas işi dayandırmamalıdır, sadəcə log yazırıq
                    $messages[] = "Telegram API Xətası: " . $response2->status();
                }
            } catch (\Exception $e) {
                Log::error("Telegram API Xətası: " . $e->getMessage());
                $messages[] = "Telegram API Bağlantı Xətası";
            }
        }

        return [
            'status' => $status,
            'message' => implode(' | ', $messages)
        ];
    }

    /**
     * [DOWNLOAD] Hələlik sadəcə status qaytarır
     */
    public function pullData()
    {
        return ['status' => true, 'message' => 'Monitorinq rejimi aktivdir.'];
    }
}
