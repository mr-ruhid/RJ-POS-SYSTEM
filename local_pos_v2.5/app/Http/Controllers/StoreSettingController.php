<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class StoreSettingController extends Controller
{
    // Tənzimləmə səhifəsini açır
    public function index()
    {
        // Bütün ayarları 'key' => 'value' formatında çəkirik
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('admin.settings.store', compact('settings'));
    }

    // Məlumatları yeniləyir
    public function update(Request $request)
    {
        // Validasiya
        $request->validate([
            'store_name' => 'required|string|max:255',
            'store_address' => 'nullable|string|max:255',
            'store_phone' => 'nullable|string|max:50',
            'store_voen' => 'nullable|string|max:20',
            'object_code' => 'nullable|string|max:20',
            'receipt_header' => 'nullable|string|max:255',
            'receipt_footer' => 'nullable|string|max:255',
        ]);

        // Formdan gələn bütün dataları (token xaric) götürüb bazaya yazırıq
        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key], // Axtarış
                ['value' => $value] // Yeniləmə
            );
        }

        return back()->with('success', 'Mağaza məlumatları uğurla yeniləndi!');
    }
}
