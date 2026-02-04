<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Promocode;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerController extends Controller
{
    /**
     * Partnyorların Siyahısı
     */
    public function index()
    {
        $systemMode = Setting::where('key', 'system_mode')->value('value') ?? 'standalone';
        $partners = Partner::with('promocodes')->latest()->paginate(20);

        return view('admin.partners.index', compact('partners', 'systemMode'));
    }

    /**
     * [AJAX] Serverdən (Node.js) Gözləyən Telegram İstəklərini Çəkmək
     */
    public function fetchTelegramRequests()
    {
        // 1. Server URL-ni götürürük (Tənzimləmələrdən)
        $serverUrl = Setting::where('key', 'server_url')->value('value');
        $apiKey = Setting::where('key', 'client_api_key')->value('value'); // Əgər serverdə yoxlama varsa

        if (!$serverUrl) {
            return response()->json(['error' => 'Server URL təyin edilməyib (Tənzimləmələrə baxın).'], 400);
        }

        try {
            // URL-i düzəldirik (Sondakı slash-ı silirik)
            $url = rtrim($serverUrl, '/');

            // Node.js API-ə sorğu göndəririk
            // Əgər Nginx-də /monitor/ istifadə edirsinizsə, URL belə ola bilər: https://domain.com/monitor/api/pending-partners
            // Node.js kodu /api/pending-partners dinləyir.

            // Sadəlik üçün birbaşa yapışdırırıq (Server URL-də /monitor varsa işləyəcək)
            $fullUrl = $url . '/api/pending-partners';

            $response = Http::timeout(5)->get($fullUrl, [
                'api_key' => $apiKey
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Serverdən xətalı cavab gəldi: ' . $response->status()], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bağlantı xətası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [POST] Telegram İstəyini Təsdiqləyib Partnyor + Promokod Yaratmaq
     */
    public function createFromTelegram(Request $request)
    {
        // 1. Validasiya
        $request->validate([
            'telegram_chat_id' => 'required|string|unique:partners,telegram_chat_id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',

            // Promokod Məlumatları
            'promo_code' => 'required|string|unique:promocodes,code',
            'discount_value' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percent,fixed',

            // Partnyor Qazancı
            'commission_percent' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            // A. Partnyoru Yaradırıq
            $partner = Partner::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'telegram_chat_id' => $request->telegram_chat_id,
                'commission_percent' => $request->commission_percent,
                'balance' => 0,
                'is_active' => true
            ]);

            // B. Promokodu Yaradırıq
            Promocode::create([
                'code' => strtoupper($request->promo_code),
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'partner_id' => $partner->id,
                'is_active' => true,
                'usage_limit' => null,
                'orders_count' => 0
            ]);

            // C. Növbəti sinxronizasiyada bu məlumatlar serverə gedəcək və server biləcək ki, bu ID artıq partnyordur.

            DB::commit();
            return back()->with('success', 'Partnyor və Promokod uğurla yaradıldı!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partner Create Error: ' . $e->getMessage());
            return back()->with('error', 'Xəta baş verdi: ' . $e->getMessage());
        }
    }

    // Digər Metodlar (Ödəniş, Silmə, Update)
    public function updateConfig(Request $request, Partner $partner)
    {
        $request->validate([
            'commission_percent' => 'required|numeric|min:0|max:100',
            'promo_code' => 'required|string',
            'discount_value' => 'required|numeric|min:0',
        ]);

        $partner->update(['commission_percent' => $request->commission_percent]);

        $promo = $partner->promocodes()->first();
        if ($promo) {
            $promo->update(['code' => strtoupper($request->promo_code), 'discount_value' => $request->discount_value]);
        } else {
            Promocode::create([
                'partner_id' => $partner->id,
                'code' => strtoupper($request->promo_code),
                'discount_type' => 'percent',
                'discount_value' => $request->discount_value,
                'is_active' => true
            ]);
        }
        return back()->with('success', 'Yeniləndi.');
    }

    public function payout(Request $request, Partner $partner)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01|max:' . $partner->balance]);
        $partner->decrement('balance', $request->amount);
        return back()->with('success', 'Ödəniş edildi.');
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();
        return back()->with('success', 'Silindi.');
    }
}
