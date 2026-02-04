<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Satış Tarixçəsi Səhifəsi (Siyahı)
     */
    public function index(Request $request)
    {
        // 1. Tarix Filteri (Varsayılan: Bu ayın əvvəlindən bu günə qədər)
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        // 2. Satışları gətiririk (Pagination ilə)
        $orders = Order::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->paginate(20);

        // 3. Statistika (Əgər view faylında statistika kartları varsa xəta verməsin)
        $paymentStats = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('payment_method', DB::raw('sum(grand_total) as total'), DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->get();

        return view('admin.sales.index', compact('orders', 'startDate', 'endDate', 'paymentStats'));
    }

    /**
     * Satışın Detallarına Baxış
     */
    public function show(Order $order)
    {
        // Məhsulları və kassiri yükləyirik
        $order->load(['items', 'user', 'promocode']);
        return view('admin.sales.show', compact('order'));
    }

    /**
     * Rəsmi Çek Çapı (Printer üçün)
     */
    public function printOfficial(Order $order)
    {
        $order->load(['items', 'user']);
        return view('admin.sales.receipt_official', compact('order'));
    }
}
