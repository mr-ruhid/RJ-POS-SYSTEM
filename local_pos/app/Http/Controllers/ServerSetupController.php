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
        // Bazadan mövcud ayarları çəkirik
        $settings = Setting::pluck('value', 'key')->toArray();

        // Server API açarı yoxdursa yaradırıq (Server rejimi üçün)
        if (!isset($settings['server_api_key'])) {
            $apiKey = 'rj_pos_' . Str::random(32);
            Setting::updateOrCreate(['key' => 'server_api_key'], ['value' => $apiKey]);
            $settings['server_api_key'] = $apiKey;
        }

        return view('admin.settings.server', compact('settings'));
    }

    public function update(Request $request)
    {
        // Validasiya
        $request->validate([
            'system_mode' => 'required|in:standalone,server,client',
            'server_url' => 'nullable|url',
            // [YENİ] Telegram API linki üçün validasiya
            'server_telegram_api' => 'nullable|url',
            'client_api_key' => 'nullable|string',
        ]);

        // 1. Rejimi Yadda Saxla
        Setting::updateOrCreate(
            ['key' => 'system_mode'],
            ['value' => $request->system_mode]
        );

        // 2. Digər məlumatları yadda saxla
        if ($request->has('server_url')) {
            // URL-in sonundakı "/" işarəsini silirik
            $cleanUrl = rtrim($request->server_url, '/');
            Setting::updateOrCreate(['key' => 'server_url'], ['value' => $cleanUrl]);
        }

        // [YENİ] Telegram API Linkini Yadda Saxla
        if ($request->has('server_telegram_api')) {
            $cleanTgUrl = rtrim($request->server_telegram_api, '/');
            Setting::updateOrCreate(['key' => 'server_telegram_api'], ['value' => $cleanTgUrl]);
        }

        if ($request->has('client_api_key')) {
            Setting::updateOrCreate(['key' => 'client_api_key'], ['value' => $request->client_api_key]);
        }

        return back()->with('success', 'Sistem rejimi və bağlantılar yeniləndi: ' . strtoupper($request->system_mode));
    }
}
