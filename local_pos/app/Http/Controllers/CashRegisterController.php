<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    public function index()
    {
        $registers = CashRegister::all();
        return view('admin.settings.registers', compact('registers'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        CashRegister::create($request->all());
        return back()->with('success', 'Kassa əlavə edildi.');
    }

    public function destroy(CashRegister $register)
    {
        $register->delete();
        return back()->with('success', 'Kassa silindi.');
    }

    public function toggle(CashRegister $register)
    {
        $register->update(['is_active' => !$register->is_active]);
        return back()->with('success', 'Kassa statusu dəyişdirildi.');
    }

    // [YENİ] Kassa Seçimi Ekranı
    public function showSelection()
    {
        $registers = CashRegister::where('is_active', true)->get();
        return view('auth.select_register', compact('registers'));
    }

    // [YENİ] Kassanı Açmaq (Sessiyaya yazır)
    public function openRegister(Request $request)
    {
        $request->validate(['register_id' => 'required|exists:cash_registers,id']);

        session(['cash_register_id' => $request->register_id]);

        return redirect()->route('pos.index');
    }

    // [YENİ] Kassanı Bağlamaq
    public function closeRegister()
    {
        session()->forget('cash_register_id');
        return redirect()->route('register.select');
    }
}
