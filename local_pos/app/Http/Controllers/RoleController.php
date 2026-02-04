<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    // 1. Rolların siyahısı
    public function index()
    {
        $roles = Role::all();
        return view('admin.roles.index', compact('roles'));
    }

    // 2. Yeni Rol Yaratmaq
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
        ]);

        Role::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name), // Məs: "Baş Kassir" -> "bas-kassir"
            'permissions' => null // Gələcəkdə icazələr sistemi üçün
        ]);

        return back()->with('success', 'Yeni rol uğurla yaradıldı.');
    }

    // 3. Rolu Silmək
    public function destroy(Role $role)
    {
        // Sistem üçün vacib olan rolları qoruyuruq
        if (in_array($role->slug, ['admin', 'cashier'])) {
            return back()->with('error', 'Sistem rollarını silmək olmaz!');
        }

        $role->delete();
        return back()->with('success', 'Rol silindi.');
    }
}
