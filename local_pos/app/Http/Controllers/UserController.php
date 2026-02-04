<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // İstifadəçilərin Siyahısı (Yalnız Admin və ya Səlahiyyətli şəxslər görə bilər)
    public function index()
    {
        // Cari istifadəçini yoxlayırıq
        if (Auth::user()->role && Auth::user()->role->name !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Bu səhifəyə giriş icazəniz yoxdur.');
        }

        $users = User::with('role')->latest()->get();
        $roles = Role::all(); // Rolları (Admin, Kassir) gətiririk

        return view('admin.users.index', compact('users', 'roles'));
    }

    // Yeni İstifadəçi Yaratmaq (Yalnız Admin)
    public function store(Request $request)
    {
        // 1. İcazə Yoxlanışı
        if (Auth::user()->role->name !== 'admin') {
            return back()->with('error', 'Yalnız Admin yeni işçi əlavə edə bilər!');
        }

        // 2. Validasiya
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        // 3. Yaratma
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Şifrəni şifrələyirik
            'role_id' => $request->role_id,
        ]);

        return back()->with('success', 'Yeni istifadəçi uğurla yaradıldı.');
    }

    // İstifadəçini Silmək
    public function destroy(User $user)
    {
        if (Auth::user()->role->name !== 'admin') {
            return back()->with('error', 'Silmək üçün icazəniz yoxdur.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Öz hesabınızı silə bilməzsiniz!');
        }

        $user->delete();
        return back()->with('success', 'İstifadəçi silindi.');
    }
}
