<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Promocode;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SyncService;

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
     * 1. Serverdən (TELEGRAM API-dən) Gözləyən İstəkləri Çəkmək (AJAX)
     * DÜZƏLİŞ: Artıq Monitorinq serverindən yox, Telegram API-dən soruşur.
     */
    public function fetchTelegramRequests()
    {
        // [DÜZƏLİŞ] 'server_url' yox, 'server_telegram_api' götürürük
        $telegramApiUrl = Setting::where('key', 'server_telegram_api')->value('value');
        $apiKey = Setting::where('key', 'client_api_key')->value('value');

        if (!$telegramApiUrl) {
            return response()->json(['error' => 'Telegram API linki təyin edilməyib (Tənzimləmələrə baxın).'], 400);
        }

        try {
            // Tənzimləmələrdə link adətən belə olur: ".../api/telegram-sync"
            // Bizə isə lazımdır: ".../api/pending-partners"
            // Ona görə sonluğu dəyişirik
            $url = str_replace('/telegram-sync', '/pending-partners', $telegramApiUrl);

            // Əgər user tənzimləmədə sadəcə domen yazıbsa, ehtiyat variant:
            if (!str_contains($url, '/api/pending-partners')) {
                 $url = rtrim($telegramApiUrl, '/') . '/api/pending-partners';
            }

            $response = Http::timeout(5)->get($url, [
                'api_key' => $apiKey
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Telegram API cavab vermədi: ' . $response->status()], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bağlantı xətası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 2. Telegram İstəyini Təsdiqləyib Partnyor + Promokod Yaratmaq
     */
    public function createFromTelegram(Request $request, SyncService $syncService)
    {
        $request->validate([
            'telegram_chat_id' => 'required|string|unique:partners,telegram_chat_id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'promo_code' => 'required|string|unique:promocodes,code',
            'discount_value' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percent,fixed',
            'commission_percent' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            // A. Partnyor Yaradılır
            $partner = Partner::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'telegram_chat_id' => $request->telegram_chat_id,
                'commission_percent' => $request->commission_percent,
                'balance' => 0,
                'is_active' => true
            ]);

            // B. Promokod Yaradılır
            Promocode::create([
                'code' => strtoupper($request->promo_code),
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'partner_id' => $partner->id,
                'is_active' => true,
                'orders_count' => 0
            ]);

            DB::commit();

            // C. Telegram API-yə Xoşgəldin Mesajı Göndərmək
            // Bunu SyncService vasitəsilə edirik, amma SyncService-də də düzəliş lazımdır
            $syncService->sendPartnerWelcome(
                $partner,
                $request->promo_code,
                $request->discount_value . ($request->discount_type == 'percent' ? '%' : ' AZN'),
                $request->commission_percent
            );

            return back()->with('success', 'Partnyor uğurla yaradıldı və Telegrama bildiriş göndərildi!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Xəta: ' . $e->getMessage());
        }
    }

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

    public function getStats(Partner $partner)
    {
        return response()->json([
            'balance' => $partner->balance,
            'commission_percent' => $partner->commission_percent,
            'promocodes' => $partner->promocodes
        ]);
    }
}
