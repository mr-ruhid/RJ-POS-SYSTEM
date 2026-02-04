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
     * 1. HESABATLAR PANELİ (Dashboard - Widgetlər)
     */
    public function index()
    {
        $backupFiles = Storage::files('backups');
        $backupCount = count(array_filter($backupFiles, fn($f) => str_ends_with($f, '.zip') || str_ends_with($f, '.sql')));
        $lastBackup = Setting::where('key', 'last_backup_date')->value('value');

        // Bu günün XALİS satışı (Qaytarmalar çıxılır)
        $todaySales = Order::whereDate('created_at', Carbon::today())
            ->sum(DB::raw('grand_total - refunded_amount'));

        $totalProducts = Product::count();

        // Kritik stok (Quantity sütununa görə)
        $criticalStockCount = Product::whereColumn('quantity', '<=', 'alert_limit')->count();

        return view('admin.reports.index', compact('backupCount', 'lastBackup', 'todaySales', 'totalProducts', 'criticalStockCount'));
    }

    /**
     * 2. MƏNFƏƏT HESABATI (ƏN DƏQİQ HESABLAMA)
     */
    public function profit(Request $request)
    {
        // Tarix Aralığı (Varsayılan: Bu ay)
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        // Satışları və detallarını gətiririk
        $orders = Order::with('items')->whereBetween('created_at', [$startDate, $endDate])->get();

        // --- HESABLAMALAR ---

        // 1. Kassa Girişi (Brutto Satış)
        $grossRevenue = $orders->sum('grand_total');

        // 2. Geri Qaytarılan Pul
        $totalRefunds = $orders->sum('refunded_amount');

        // 3. Xalis Satış (Net Revenue)
        $netRevenue = $grossRevenue - $totalRefunds;

        // 4. Maya Dəyəri (Cost)
        // Satılan malın mayasından, qaytarılan malın mayasını çıxmalıyıq
        $totalSoldCost = 0;
        $totalReturnedCost = 0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                // Satılanın mayası
                $totalSoldCost += ($item->cost * $item->quantity);

                // Qaytarılanın mayası (Əgər qaytarılıbsa)
                if ($item->returned_quantity > 0) {
                    $totalReturnedCost += ($item->cost * $item->returned_quantity);
                }
            }
        }

        $netCost = $totalSoldCost - $totalReturnedCost;

        // 5. Xərclər (Vergi + Komissiya)
        $totalTax = $orders->sum('total_tax');
        $totalCommission = $orders->sum('total_commission');

        // Məlumat üçün endirimlər (Mənfəətə təsir etmir, çünki satışdan artıq çıxılıb)
        $totalDiscount = $orders->sum('total_discount');

        // 6. XALİS MƏNFƏƏT
        // Düstur: (Xalis Satış) - (Xalis Maya) - Vergi - Komissiya
        $netProfit = $netRevenue - $netCost - $totalTax - $totalCommission;

        return view('admin.reports.profit', compact(
            'startDate', 'endDate',
            'grossRevenue', 'totalRefunds', 'netRevenue',
            'totalSoldCost', 'totalReturnedCost', 'netCost',
            'totalTax', 'totalDiscount', 'totalCommission', 'netProfit'
        ));
    }

    /**
     * 3. STOK HESABATI
     */
    public function stock()
    {
        // Yalnız stoku olan məhsulları götürürük
        $products = Product::where('quantity', '>', 0)->get();

        $totalCostValue = 0;
        $totalSaleValue = 0;

        foreach ($products as $product) {
            $qty = $product->quantity;

            // Maya dəyəri (Alış)
            $totalCostValue += ($qty * $product->cost_price);

            // Satış dəyəri
            $totalSaleValue += ($qty * $product->selling_price);
        }

        $potentialProfit = $totalSaleValue - $totalCostValue;

        // Partiyalar üzrə siyahı (Əgər batch istifadə edirsinizsə)
        $batches = ProductBatch::with('product')
            ->where('current_quantity', '>', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.reports.stock', compact('totalCostValue', 'totalSaleValue', 'potentialProfit', 'batches'));
    }

    /**
     * 4. PARTNYOR HESABATI
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

        // Ödəniş növləri üzrə statistika (Xalis satış)
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
