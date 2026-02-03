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
use Carbon\Carbon;

class SyncService
{
    protected $serverUrl;

    public function __construct()
    {
        $this->serverUrl = Setting::where('key', 'server_url')->value('value');
        if ($this->serverUrl) {
            $this->serverUrl = rtrim($this->serverUrl, '/');
        }
    }

    public function pushData()
    {
        if (!$this->serverUrl) return ['status' => false, 'message' => 'Server URL yoxdur'];

        // 1. STATİSTİKALAR
        $today = Carbon::today();
        $todayStats = Order::whereDate('created_at', $today)
            ->select(
                DB::raw('sum(grand_total) as total_sales'),
                DB::raw('count(*) as count_sales'),
                DB::raw('sum(grand_total - total_cost - total_tax) as net_profit')
            )->first();

        // Anbar dəyərləri
        $stockStats = DB::table('product_batches')
            ->where('current_quantity', '>', 0)
            ->select(DB::raw('SUM(current_quantity * cost_price) as total_cost_value'))->first();

        $totalSaleValue = DB::table('product_batches')
            ->join('products', 'product_batches.product_id', '=', 'products.id')
            ->where('product_batches.current_quantity', '>', 0)
            ->sum(DB::raw('product_batches.current_quantity * products.selling_price'));

        $partnerCount = class_exists(Partner::class) ? Partner::count() : 0;
        $criticalStockCount = Product::whereRaw('alert_limit > quantity')->count();

        // 2. SİYAHILAR (YENİ ƏLAVƏ)
        // Məhsul siyahısı (Stoku olanlar və ya hamısı)
        // Yaddaşa qənaət üçün sadəcə lazım olan sütunları seçirik
        $productsList = Product::select('name', 'barcode', 'quantity', 'selling_price', 'cost_price', 'is_active')
            ->orderBy('quantity', 'desc') // Çoxdan aza doğru
            ->get()
            ->toArray();

        // Promokod siyahısı
        $promocodesList = class_exists(Promocode::class)
            ? Promocode::withCount('orders')->get()->toArray()
            : [];

        // Son satışlar
        $latestOrders = Order::latest()->take(10)->get()->map(function($order) {
            return [
                'receipt_code' => $order->receipt_code,
                'grand_total' => $order->grand_total,
                'payment_method' => $order->payment_method,
                'time' => $order->created_at->format('H:i:s'),
                'items_count' => $order->items->count()
            ];
        });

        // TAM PAKET
        $payload = [
            'stats' => [
                'today_sales' => $todayStats->total_sales ?? 0,
                'today_count' => $todayStats->count_sales ?? 0,
                'today_profit' => $todayStats->net_profit ?? 0,
                'warehouse_cost' => $stockStats->total_cost_value ?? 0,
                'warehouse_sale' => $totalSaleValue ?? 0,
                'potential_profit' => $totalSaleValue - ($stockStats->total_cost_value ?? 0),
                'critical_stock' => $criticalStockCount,
                'partner_count' => $partnerCount,
            ],
            'latest_orders' => $latestOrders,
            'products' => $productsList,      // <--- Yeni
            'promocodes' => $promocodesList   // <--- Yeni
        ];

        try {
            // Data böyük ola bilər deyə timeout artırılır
            Http::timeout(15)->post($this->serverUrl . '/api/report', [
                'type' => 'full_report',
                'payload' => $payload
            ]);

            return ['status' => true, 'message' => 'Tam hesabat Monitora göndərildi'];

        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Monitor Xətası: ' . $e->getMessage()];
        }
    }

    public function pullData()
    {
        return ['status' => true, 'message' => 'Monitorinq rejimi aktivdir.'];
    }
}
