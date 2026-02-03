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

        $todaySales = Order::whereDate('created_at', Carbon::today())->sum('grand_total');
        $totalProducts = Product::count();

        $criticalStockCount = Product::whereRaw('alert_limit > (select coalesce(sum(current_quantity), 0) from product_batches where product_batches.product_id = products.id)')->count();

        return view('admin.reports.index', compact('backupCount', 'lastBackup', 'todaySales', 'totalProducts', 'criticalStockCount'));
    }

    /**
     * 2. MƏNFƏƏT HESABATI
     */
    public function profit(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();

        $totalRevenue = $orders->sum('grand_total');
        $totalCost = $orders->sum('total_cost');
        $totalTax = $orders->sum('total_tax');
        $totalDiscount = $orders->sum('total_discount');

        $netProfit = $totalRevenue - $totalTax - $totalCost;

        return view('admin.reports.profit', compact(
            'startDate', 'endDate',
            'totalRevenue', 'totalCost', 'totalTax', 'totalDiscount', 'netProfit'
        ));
    }

    /**
     * 3. STOK HESABATI (Batch Code Regex Fix)
     * Anbarda olan malın real dəyəri (Vergi daxil)
     */
    public function stock()
    {
        // Bütün aktiv partiyaları gətiririk (hesablama üçün)
        // cursor() istifadə edirik ki, yaddaş dolmasın
        $batchesCursor = ProductBatch::with('product')->where('current_quantity', '>', 0)->cursor();

        $totalCostValue = 0;
        $totalSaleValue = 0;

        foreach ($batchesCursor as $batch) {
            $qty = $batch->current_quantity;
            $costPrice = $batch->cost_price;

            // --- VERGİ FAİZİNİ TAPMAQ ---
            $taxRate = 0;
            if ($batch->batch_code && preg_match('/\((\d+(?:\.\d+)?)%\)/', $batch->batch_code, $matches)) {
                $taxRate = (float) $matches[1];
            }

            // 1. Maya Dəyəri (Vergi ilə birlikdə)
            // Maya = (Say * Alış) + (Say * Alış * VergiFaizi / 100)
            $batchBaseCost = $qty * $costPrice;
            $batchTaxCost = $batchBaseCost * ($taxRate / 100);

            $totalCostValue += ($batchBaseCost + $batchTaxCost);

            // 2. Satış Dəyəri
            if ($batch->product) {
                $totalSaleValue += ($qty * $batch->product->selling_price);
            }
        }

        // Gözlənilən Mənfəət
        $potentialProfit = $totalSaleValue - $totalCostValue;

        // Cədvəl üçün paginasiya
        $batches = ProductBatch::with('product')
            ->where('current_quantity', '>', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.reports.stock', compact('totalCostValue', 'totalSaleValue', 'potentialProfit', 'batches'));
    }

    /**
     * 4. PARTNYOR VƏ PROMOKOD HESABATI
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
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        $orders = Order::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->paginate(15);

        $paymentStats = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('payment_method', DB::raw('sum(grand_total) as total'), DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->get();

        return view('admin.reports.sales', compact('orders', 'paymentStats', 'startDate', 'endDate'));
    }
}
