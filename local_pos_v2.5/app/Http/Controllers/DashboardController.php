<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\SyncService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\RJUpdaterService;

class DashboardController extends Controller
{
    // Ana Səhifəni Yükləyir
    public function index()
    {
        $today = Carbon::today();

        $todaysOrders = Order::whereDate('created_at', $today)->get();
        $totalSalesToday = $todaysOrders->sum('grand_total');
        $totalOrdersToday = $todaysOrders->count();
        // Mənfəət = Satış - Cost (əgər varsa)
        $totalProfitToday = $todaysOrders->sum('grand_total') - $todaysOrders->sum('total_cost');

        // Kritik Stok (Yeni 'quantity' sütununa əsasən)
        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('quantity', '<=', 'alert_limit')
            ->take(5)
            ->get();

        // Son satışlar
        $recentOrders = Order::latest()->take(5)->get();

        $systemMode = Setting::where('key', 'system_mode')->value('value') ?? 'standalone';

        // --- RJ UPDATER (SİSTEM YENİLƏMƏSİ) ---
        $updateInfo = null;
        try {
            // Updater həm Server, həm Client rejimində işləyə bilər (Versiya yeniləməsi üçün)
            $updater = new RJUpdaterService();
            $updateInfo = $updater->checkUpdate();
        } catch (\Exception $e) {
            // Xəta olsa sistem dayanmasın
        }

        return view('dashboard', compact(
            'totalSalesToday',
            'totalOrdersToday',
            'totalProfitToday',
            'lowStockProducts',
            'recentOrders',
            'systemMode',
            'updateInfo'
        ));
    }

    // Manual və Avtomatik Sinxronizasiya (Vahid Kod Məntiqi)
    public function syncNow(Request $request, SyncService $syncService)
    {
        // 1. Rejimi Yoxlayırıq (Universal yanaşma)
        // Kod eynidir, amma davranış rejimə görə dəyişir.
        $mode = Setting::where('key', 'system_mode')->value('value');

        // Əgər SERVER və ya STANDALONE (Lokal) rejimdirsə, başqa yerə qoşulmağa ehtiyac yoxdur.
        if ($mode !== 'client') {
            $msg = ($mode === 'server')
                ? 'Bu cihaz Əsas Serverdir. Məlumatlar avtomatik qəbul edilir, göndərilmir.'
                : 'Lokal rejimdə sinxronizasiya deaktivdir.';

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('info', $msg);
        }

        // 2. Yalnız CLIENT (Mağaza) rejimində işə düşür
        // Serverə qoşulur, məlumatları göndərir və yenilikləri alır.
        $pushResult = $syncService->pushData(); // Yuxarı Axın (Upload)
        $pullResult = $syncService->pullData(); // Aşağı Axın (Download)

        $message = "Göndərildi: {$pushResult['message']} | Qəbul edildi: {$pullResult['message']}";
        $status = $pushResult['status'] && $pullResult['status'];

        // AJAX sorğusu üçün JSON qaytar (Bağlantını Yoxla düyməsi üçün)
        if ($request->ajax()) {
            return response()->json([
                'success' => $status,
                'message' => $message
            ]);
        }

        // Normal sorğu üçün redirect
        if ($status) {
            return back()->with('success', "Sinxronizasiya Uğurlu!\n" . $message);
        } else {
            return back()->with('error', "Xəta: " . $message);
        }
    }
}
