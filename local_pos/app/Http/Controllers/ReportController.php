<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Partner;
use App\Models\Setting;
use App\Models\Promocode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * 1. HESABATLAR PANELİ (Dashboard)
     */
    public function index()
    {
        $backupFiles = Storage::files('backups');
        $backupCount = count(array_filter($backupFiles, fn($f) => str_ends_with($f, '.zip') || str_ends_with($f, '.sql')));
        $lastBackup = Setting::where('key', 'last_backup_date')->value('value');

        // Günlük Net Satış (Qaytarmalar çıxılmaqla)
        $todaySales = Order::whereDate('created_at', Carbon::today())
            ->sum(DB::raw('grand_total - refunded_amount'));

        $totalProducts = Product::count();

        // Kritik stok
        $criticalStockCount = Product::whereColumn('quantity', '<=', 'alert_limit')->count();

        return view('admin.reports.index', compact('backupCount', 'lastBackup', 'todaySales', 'totalProducts', 'criticalStockCount'));
    }

    /**
     * 2. MƏNFƏƏT HESABATI (YENİLƏNMİŞ - DƏQİQ HESABLAMA)
     */
    public function profit(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        // Satışları və içindəki məhsulları gətiririk
        $orders = Order::with('items')->whereBetween('created_at', [$startDate, $endDate])->get();

        // 1. Ümumi Kassa Girişi (Brutto Satış)
        $grossRevenue = $orders->sum('grand_total');

        // 2. Geri Qaytarılan Məbləğ
        $totalRefunds = $orders->sum('refunded_amount');

        // 3. Xalis Satış (Net Revenue) - View faylında tələb olunan dəyişən
        $netRevenue = $grossRevenue - $totalRefunds;

        // 4. Maya Dəyəri Hesablaması
        $totalCost = 0; // Satılan malların mayası
        $totalReturnedCost = 0; // Qaytarılan malların mayası

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                // Satılan malın mayası
                $totalCost += ($item->cost * $item->quantity);

                // Qaytarılan malın mayası (Anbara qayıtdığı üçün xərc deyil)
                if ($item->returned_quantity > 0) {
                    $totalReturnedCost += ($item->cost * $item->returned_quantity);
                }
            }
        }

        // Xalis Maya Dəyəri (Yalnız müştəridə qalan mallar)
        $netCost = $totalCost - $totalReturnedCost;

        // 5. Vergi, Endirim və Komissiya
        $totalTax = $orders->sum('total_tax');
        $totalDiscount = $orders->sum('total_discount');
        $totalCommission = $orders->sum('total_commission');

        // 6. XALİS MƏNFƏƏT
        // (Xalis Satış) - (Xalis Maya) - Vergi - Komissiya
        $netProfit = $netRevenue - $netCost - $totalTax - $totalCommission;

        return view('admin.reports.profit', compact(
            'startDate', 'endDate',
            'grossRevenue', 'totalRefunds', 'netRevenue',
            'totalCost', 'totalReturnedCost', 'netCost',
            'totalTax', 'totalDiscount', 'totalCommission', 'netProfit'
        ));
    }

    /**
     * 3. STOK HESABATI
     */
    public function stock()
    {
        // Product cədvəlindən ümumi dəyərləri hesablayırıq
        $products = Product::where('quantity', '>', 0)->get();

        $totalCostValue = 0;
        $totalSaleValue = 0;

        foreach ($products as $product) {
            $qty = $product->quantity;
            $baseCost = $qty * $product->cost_price;

            // Vergi dərəcəsi varsa maya dəyərini artırırıq
            $taxCost = $baseCost * ($product->tax_rate / 100);

            $totalCostValue += ($baseCost + $taxCost);
            $totalSaleValue += ($qty * $product->selling_price);
        }

        $potentialProfit = $totalSaleValue - $totalCostValue;

        // Cədvəl üçün ProductBatch və ya Product istifadə edə bilərik.
        // Ətraflı məlumat üçün ProductBatch (Partiyalar) daha yaxşıdır.
        $batches = ProductBatch::with('product')
            ->where('current_quantity', '>', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.reports.stock', compact('totalCostValue', 'totalSaleValue', 'potentialProfit', 'batches'));
    }

    /**
     * 4. PARTNYORLAR HESABATI
     */
    public function partners()
    {
        $partners = Partner::with(['promocodes' => function($query) {
            $query->withCount('orders')
                  ->withSum('orders', 'grand_total');
        }])->get();

        $promocodes = Promocode::withCount('orders')
            ->orderBy('orders_count', 'desc')
            ->get();

        return view('admin.reports.partners', compact('partners', 'promocodes'));
    }

    /**
     * 5. SATIŞ HESABATI
     */
    public function sales(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        $orders = Order::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->paginate(15);

        // Ödəniş növləri üzrə statistika (Net satış - Qaytarma çıxılmış)
        $paymentStats = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'payment_method',
                DB::raw('sum(grand_total - refunded_amount) as total'),
                DB::raw('count(*) as count')
            )
            ->groupBy('payment_method')
            ->get();

        return view('admin.reports.sales', compact('orders', 'paymentStats', 'startDate', 'endDate'));
    }
}
