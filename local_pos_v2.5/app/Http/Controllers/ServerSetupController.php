<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServerSetupController extends Controller
{
    public function index()
    {
        // Ayarları array formatında çəkirik
        $settings = Setting::pluck('value', 'key')->toArray();

        // [Sizin Məntiq]: API Açar yoxdursa, səhifə açılan kimi yarat
        // Bu çox yaxşı fikirdir, çünki user dərhal açarı görür.
        if (!isset($settings['server_api_key'])) {
            $apiKey = 'rj_pos_' . Str::random(32);
            Setting::updateOrCreate(['key' => 'server_api_key'], ['value' => $apiKey]);
            $settings['server_api_key'] = $apiKey;
        }

        // View yolu: Sizin strukturda 'admin.settings.server' olduğu görünür
        return view('admin.settings.server', compact('settings'));
    }

    public function update(Request $request)
    {
        // Validasiya
        $request->validate([
            'system_mode' => 'required|in:standalone,server,client',
            'server_url' => 'nullable|url',
            'client_api_key' => 'nullable|string',
        ]);

        // 1. Rejimi Yadda Saxla
        Setting::updateOrCreate(
            ['key' => 'system_mode'],
            ['value' => $request->system_mode]
        );

        // 2. Digər məlumatları yadda saxla
        if ($request->has('server_url')) {
            // [VACİB DÜZƏLİŞ]: URL-in sonundakı "/" işarəsini silirik.
            // Bu olmasa, "https://site.com//api" problemi yaranır və sistem işləmir.
            $cleanUrl = rtrim($request->server_url, '/');

            Setting::updateOrCreate(['key' => 'server_url'], ['value' => $cleanUrl]);
        }

        if ($request->has('client_api_key')) {
            Setting::updateOrCreate(['key' => 'client_api_key'], ['value' => $request->client_api_key]);
        }

        return back()->with('success', 'Sistem rejimi uğurla yeniləndi: ' . strtoupper($request->system_mode));
    }
}
