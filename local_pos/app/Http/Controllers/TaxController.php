<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    // Siyahı və Əlavə etmə səhifəsi
    public function index()
    {
        // Vergiləri bazadan çəkirik
        $taxes = Tax::latest()->get();
        return view('admin.settings.taxes', compact('taxes'));
    }

    // Yeni Vergi Əlavə Et
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        Tax::create([
            'name' => $request->name,
            'rate' => $request->rate,
            'is_active' => true
        ]);

        return back()->with('success', 'Vergi dərəcəsi əlavə edildi.');
    }

    // Silmək
    public function destroy(Tax $tax)
    {
        $tax->delete();
        return back()->with('success', 'Vergi dərəcəsi silindi.');
    }

    // Status dəyişmək (Aktiv/Passiv)
    public function toggle(Tax $tax)
    {
        $tax->update(['is_active' => !$tax->is_active]);
        return back()->with('success', 'Status yeniləndi.');
    }
}
