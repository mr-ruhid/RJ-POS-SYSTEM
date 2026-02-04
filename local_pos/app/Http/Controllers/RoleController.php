<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
            'slug' => Str::slug($request->name),
            'permissions' => json_encode($request->permissions ?? []) // İcazələr boşdursa boş massiv yazsın
        ]);

        return back()->with('success', 'Yeni rol uğurla yaradıldı.');
    }

    // 3. Rolu Yeniləmək (İcazələri Dəyişmək) - [YENİ]
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array' // İcazələr massiv olaraq gəlir
        ]);

        // Admin rolunu dəyişməyə icazə vermirik (Təhlükəsizlik üçün)
        if ($role->slug === 'admin') {
            return back()->with('error', 'Admin rolunu redaktə etmək olmaz.');
        }

        $data = [
            'name' => $request->name,
            'permissions' => json_encode($request->permissions ?? []) // Checkbox-dan gələnləri JSON edirik
        ];

        // Əgər sistem rolu (kassir) deyilsə, slug-ı da yeniləyə bilərik
        // Kassirin slug-ı "cashier" qalmalıdır ki, kodlar işləsin
        if (!in_array($role->slug, ['cashier'])) {
            $data['slug'] = Str::slug($request->name);
        }

        $role->update($data);

        return back()->with('success', 'Rol icazələri yeniləndi.');
    }

    // 4. Rolu Silmək
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
