<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\CashRegister; // <--- Kassalar Modelini Əlavə Etdik
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 1. Adminlərin Siyahısı (İdarəçilər)
    public function admins()
    {
        // Rolu 'super_admin' və ya 'admin' olanları gətiririk
        $users = User::whereHas('role', function($q) {
            $q->whereIn('slug', ['super_admin', 'admin']);
        })->with('role')->latest()->paginate(10);

        // Yeni əlavə etmək üçün rollar (yalnız admin rolu görünür)
        $roles = Role::where('slug', 'admin')->get();

        return view('admin.users.admins', compact('users', 'roles'));
    }

    // 2. Kassirlərin Siyahısı
    public function cashiers()
    {
        // Rolu 'kassa' olanları gətiririk
        $users = User::whereHas('role', function($q) {
            $q->where('slug', 'kassa');
        })->with('role')->latest()->paginate(10);

        // Yeni əlavə etmək üçün 'kassa' rolunu tapırıq
        $kassaRole = Role::where('slug', 'kassa')->first();

        // Kassaları gətiririk ki, seçim etmək olsun (YENİ)
        $cashRegisters = CashRegister::where('is_active', true)->get();

        return view('admin.users.cashiers', compact('users', 'kassaRole', 'cashRegisters'));
    }

    // Yeni İstifadəçi Yarat (Həm admin, həm kassir üçün ortaqdır)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
            'cash_register_id' => 'nullable|exists:cash_registers,id', // Kassa ID-si validasiyası
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'cash_register_id' => $request->cash_register_id, // Kassa təyini (əgər varsa)
            'is_active' => true,
        ]);

        return back()->with('success', 'Yeni istifadəçi əlavə edildi!');
    }

    // İstifadəçini Sil
    public function destroy(User $user)
    {
        // Super Admin silinə bilməz
        if ($user->role && $user->role->slug === 'super_admin') {
            return back()->with('error', 'Super Admin silinə bilməz!');
        }

        // Özünü silə bilməz
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Öz hesabınızı silə bilməzsiniz!');
        }

        $user->delete();
        return back()->with('success', 'İstifadəçi silindi.');
    }
}
