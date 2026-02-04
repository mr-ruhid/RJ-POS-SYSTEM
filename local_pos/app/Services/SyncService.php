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
use Illuminate\Support\Facades\Schema;
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

        // 2. MƏHSUL SİYAHISI
        $productsList = Product::select('id', 'name', 'barcode', 'quantity', 'selling_price', 'cost_price', 'is_active')
            ->orderBy('quantity', 'desc')
            ->get()
            ->toArray();

        // 3. PROMOKOD SİYAHISI (partnyor_id ilə)
        $promocodesList = [];
        if (class_exists(Promocode::class)) {
            $promos = Promocode::select('id', 'code', 'discount_type', 'discount_value', 'partner_id', 'is_active')->get();

            foreach ($promos as $promo) {
                // Bu promokodun istifadə sayını hesablayırıq
                $ordersCount = Order::where('promocode_id', $promo->id)->count();

                $promocodesList[] = [
                    'id' => $promo->id,
                    'code' => $promo->code,
                    'discount_type' => $promo->discount_type,
                    'discount_value' => $promo->discount_value,
                    'partner_id' => $promo->partner_id,
                    'is_active' => $promo->is_active,
                    'orders_count' => $ordersCount
                ];
            }
        }

        // 4. PARTNYOR SİYAHISI (promokod və komissiya məlumatları ilə)
        $partnersList = [];
        if (class_exists(Partner::class)) {
            // Yoxlayırıq: commission_rate sütunu varmı?
            $hasCommissionRate = Schema::hasColumn('partners', 'commission_rate');

            // Select query-ni dinamik qururuq
            $selectColumns = ['id', 'name', 'phone', 'telegram_chat_id', 'balance'];
            if ($hasCommissionRate) {
                $selectColumns[] = 'commission_rate';
            }

            $partners = Partner::select($selectColumns)->get();

            foreach ($partners as $partner) {
                // Bu partnyora aid promokodlar
                $partnerPromos = Promocode::where('partner_id', $partner->id)
                    ->select('code', 'discount_value', 'discount_type')
                    ->get()
                    ->toArray();

                // Bu partnyorun promokod ID-lərini tapırıq
                $promoIds = Promocode::where('partner_id', $partner->id)->pluck('id')->toArray();

                // Bu partnyorun bugünkü satışları
                $todaySales = 0;
                if (!empty($promoIds)) {
                    $todaySales = Order::whereDate('created_at', $today)
                        ->whereIn('promocode_id', $promoIds)
                        ->sum('grand_total');
                }

                // Ümumi satışlar
                $totalSales = 0;
                if (!empty($promoIds)) {
                    $totalSales = Order::whereIn('promocode_id', $promoIds)
                        ->sum('grand_total');
                }

                // Komissiya hesablama (əgər sütun varsa)
                $commissionRate = $hasCommissionRate && isset($partner->commission_rate)
                    ? $partner->commission_rate
                    : 10.00; // Default 10%

                $commission = $totalSales * ($commissionRate / 100);

                $partnersList[] = [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'phone' => $partner->phone,
                    'telegram_chat_id' => $partner->telegram_chat_id,
                    'balance' => $partner->balance,
                    'commission_rate' => $commissionRate,
                    'promocodes' => $partnerPromos,
                    'today_sales' => $todaySales,
                    'total_sales' => $totalSales,
                    'total_commission' => $commission
                ];
            }
        }

        // 5. ANBAR (Batch siyahısı)
        $batchesList = [];
        if (class_exists(ProductBatch::class)) {
            $batchesList = ProductBatch::where('current_quantity', '>', 0)
                ->join('products', 'product_batches.product_id', '=', 'products.id')
                ->select(
                    'product_batches.id',
                    'product_batches.batch_code',
                    'product_batches.cost_price',
                    'product_batches.current_quantity',
                    'product_batches.initial_quantity',
                    'products.name as product_name',
                    'products.barcode'
                )
                ->orderBy('product_batches.created_at', 'desc')
                ->get()
                ->toArray();
        }

        // 6. LOTEREYA SİYAHISI (lotereya kodlu sifarişlər)
        $lotteryOrders = [];
        if (Schema::hasColumn('orders', 'lottery_code')) {
            $lotteryOrders = Order::whereNotNull('lottery_code')
                ->latest()
                ->take(50)
                ->select('receipt_code', 'lottery_code', 'grand_total', 'created_at')
                ->get()
                ->map(function($order) {
                    return [
                        'receipt_code' => $order->receipt_code,
                        'lottery_code' => $order->lottery_code,
                        'grand_total' => $order->grand_total,
                        'time' => $order->created_at->format('d.m.Y H:i')
                    ];
                })
                ->toArray();
        }

        // 7. SON SİFARİŞLƏR (promokod məlumatı ilə)
        $latestOrders = Order::latest()
            ->take(10)
            ->select('id', 'receipt_code', 'grand_total', 'payment_method', 'promocode_id', 'created_at')
            ->get()
            ->map(function($order) {
                $promoCode = null;
                if ($order->promocode_id) {
                    $promo = Promocode::find($order->promocode_id);
                    $promoCode = $promo ? $promo->code : null;
                }

                // OrderItem sayını tapırıq
                $itemsCount = DB::table('order_items')->where('order_id', $order->id)->count();

                return [
                    'receipt_code' => $order->receipt_code,
                    'grand_total' => $order->grand_total,
                    'payment_method' => $order->payment_method,
                    'promo_code' => $promoCode,
                    'time' => $order->created_at->format('H:i:s'),
                    'items_count' => $itemsCount
                ];
            })
            ->toArray();

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
            'products' => $productsList,
            'promocodes' => $promocodesList,
            'partners' => $partnersList,
            'batches' => $batchesList,
            'lottery_orders' => $lotteryOrders
        ];

        try {
            // Data böyük ola bilər deyə timeout artırılır
            $response = Http::timeout(15)->post($this->serverUrl . '/api/report', [
                'type' => 'full_report',
                'payload' => $payload
            ]);

            if ($response->successful()) {
                return ['status' => true, 'message' => 'Tam hesabat Monitora göndərildi'];
            } else {
                return ['status' => false, 'message' => 'Server cavab vermədi: ' . $response->status()];
            }

        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Monitor Xətası: ' . $e->getMessage()];
        }
    }

    public function pullData()
    {
        return ['status' => true, 'message' => 'Monitorinq rejimi aktivdir.'];
    }
}
