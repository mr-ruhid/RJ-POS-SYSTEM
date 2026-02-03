<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class LotteryController extends Controller
{
    // Lotoreya Siyahısı
    public function index()
    {
        // Yalnız lotoreya kodu olan satışları gətiririk
        $lotteries = Order::whereNotNull('lottery_code')
                          ->with('user') // Kassiri bilmək üçün
                          ->latest()
                          ->paginate(20);

        return view('admin.lotteries.index', compact('lotteries'));
    }

    // Gələcəkdə qalib təyin etmək funksiyası bura yazıla bilər
}
