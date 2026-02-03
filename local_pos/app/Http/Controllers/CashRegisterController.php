<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    // Siyahı
    public function index()
    {
        $registers = CashRegister::latest()->get();
        return view('admin.settings.registers', compact('registers'));
    }

    // Yeni Kassa Yarat
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:cash_registers,code',
            'ip_address' => 'nullable|ipv4',
        ]);

        CashRegister::create($request->only('name', 'code', 'ip_address'));

        return back()->with('success', 'Kassa uğurla əlavə edildi.');
    }

    // Aktiv/Deaktiv Et
    public function toggle(CashRegister $register)
    {
        $register->update(['is_active' => !$register->is_active]);
        return back()->with('success', 'Status dəyişdirildi.');
    }

    // Sil
    public function destroy(CashRegister $register)
    {
        // Əgər kassa açıqdırsa (növbə varsa) silmək olmaz
        if($register->status === 'open') {
             return back()->withErrors(['error' => 'Açıq olan kassanı silmək olmaz! Əvvəl növbəni bağlayın.']);
        }

        $register->delete();
        return back()->with('success', 'Kassa silindi.');
    }
}
