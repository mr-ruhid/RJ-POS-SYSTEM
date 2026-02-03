<?php

namespace App\Http\Controllers;

use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromocodeController extends Controller
{
    // Promokod Siyahısı və Yaratma Forması
    public function index()
    {
        // Yalnız mağaza kodlarını gətiririk
        $promocodes = Promocode::where('type', 'store')
                               ->latest()
                               ->paginate(15);

        return view('admin.promocodes.index', compact('promocodes'));
    }

    // Yeni Promokod Yarat (Sadə)
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:promocodes,code|max:20',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        Promocode::create([
            'code' => strtoupper($request->code),
            'type' => 'store', // Məcburi olaraq 'store' seçirik
            'partner_id' => null, // Partnyor yoxdur
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'commission_type' => 'percent', // Default
            'commission_value' => 0, // Komissiya yoxdur
            'usage_limit' => $request->usage_limit,
            'expires_at' => $request->expires_at,
            'is_active' => true
        ]);

        return back()->with('success', 'Promokod uğurla yaradıldı!');
    }

    // Silmək
    public function destroy(Promocode $promocode)
    {
        $promocode->delete();
        return back()->with('success', 'Promokod silindi.');
    }
}
