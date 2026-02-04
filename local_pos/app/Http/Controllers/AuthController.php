<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // ==========================================
    // 1. ADMIN GİRİŞİ
    // ==========================================
    public function showAdminLoginForm()
    {
        // Əgər artıq giriş edibsə, roluna uyğun yönləndir
        if (Auth::check()) {
            return $this->redirectBasedOnRole();
        }
        return view('auth.login', ['url' => 'admin']);
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            $user = Auth::user();

            // Rol yoxlanışı (Admin olmalıdır)
            if ($user->role && $user->role->name === 'admin') {
                $request->session()->regenerate();
                return redirect()->route('dashboard')->with('success', 'Xoş gəldiniz, Admin!');
            } else {
                Auth::logout();
                return back()->withErrors(['email' => 'Bu giriş yalnız İdarəçilər üçündür.']);
            }
        }

        return back()->withErrors(['email' => 'Email və ya şifrə yanlışdır.'])->onlyInput('email');
    }

    // ==========================================
    // 2. KASSİR (PERSONAL) GİRİŞİ
    // ==========================================
    public function showStaffLoginForm()
    {
        // Əgər artıq giriş edibsə, roluna uyğun yönləndir
        if (Auth::check()) {
            return $this->redirectBasedOnRole();
        }
        return view('auth.login', ['url' => 'staff']);
    }

    public function staffLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            $user = Auth::user();
            $request->session()->regenerate();

            // Girişdən dərhal sonra roluna uyğun yönləndir
            return $this->redirectBasedOnRole();
        }

        return back()->withErrors(['email' => 'Məlumatlar yanlışdır.'])->onlyInput('email');
    }

    // ==========================================
    // 3. ÇIXIŞ
    // ==========================================
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }

    // ==========================================
    // KÖMƏKÇİ: Rola görə yönləndirmə
    // ==========================================
    private function redirectBasedOnRole()
    {
        $user = Auth::user();

        if ($user->role && $user->role->name === 'admin') {
            return redirect()->route('dashboard');
        }

        // Admin deyilsə (Kassirdirsə) Kassa Seçiminə getsin
        return redirect()->route('register.select');
    }
}
