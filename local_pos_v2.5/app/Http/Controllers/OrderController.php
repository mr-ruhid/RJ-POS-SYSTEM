<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Satış Tarixçəsi Siyahısı
    public function index()
    {
        // Satışları ən sondan əvvələ doğru gətiririk
        $orders = Order::with(['user', 'items'])->latest()->paginate(20);

        return view('admin.sales.index', compact('orders'));
    }

    // Satışın Detalları (Çek Görüntüsü - Ekranda baxmaq üçün)
    public function show(Order $order)
    {
        $order->load(['user', 'items']);

        // Mağaza məlumatlarını bazadan çəkirik
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('admin.sales.show', compact('order', 'settings'));
    }

    // Rəsmi Çek Çapı (Avtomatik açılan pəncərə üçün) - BU METOD ƏLAVƏ EDİLDİ
    public function printOfficial(Order $order)
    {
        $order->load(['user', 'items']);

        // Tənzimləmələri çəkirik
        $settings = Setting::pluck('value', 'key')->toArray();

        // Rəsmi qəbz şablonunu qaytarır
        return view('admin.sales.receipt_official', compact('order', 'settings'));
    }
}
