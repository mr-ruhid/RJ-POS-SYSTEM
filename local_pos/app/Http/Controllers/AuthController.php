<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // Giriş səhifəsini göstər
    public function showLoginForm()
    {
        // Əgər artıq giriş edibsə, ana səhifəyə at
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    // Giriş prosesi
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // "Remember me" (Məni xatırla) funksiyası
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Uğurlu giriş
            return redirect()->route('dashboard')->with('success', 'Xoş gəldiniz, ' . Auth::user()->name);
        }

        // Uğursuz giriş
        return back()->withErrors([
            'email' => 'Daxil edilən məlumatlar yanlışdır.',
        ])->onlyInput('email');
    }

    // Çıxış prosesi
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
