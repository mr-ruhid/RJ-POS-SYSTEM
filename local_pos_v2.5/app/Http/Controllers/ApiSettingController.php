<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class ApiSettingController extends Controller
{
    // API Ayarları Səhifəsi
    public function index()
    {
        // Bütün tənzimləmələri çəkirik
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('admin.settings.api', compact('settings'));
    }

    // Yadda Saxla
    public function update(Request $request)
    {
        // Gələcəkdə bura digər API-lər də əlavə olunacaq
        $data = $request->validate([
            'telegram_bot_token' => 'nullable|string',
            'telegram_admin_id' => 'nullable|string', // Adminin öz ID-si (test üçün)
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'API tənzimləmələri yeniləndi!');
    }
}
